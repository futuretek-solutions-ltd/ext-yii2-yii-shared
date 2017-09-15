<?php

namespace futuretek\yii\shared;

use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class Controller
 * ----------------
 * Controller parent used in our company.
 *
 * Extend standard controller with following features:
 * * Methods to create/update multi-model forms (1:n relations)
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Controller extends \yii\web\Controller
{
    /**
     * Create multiform models
     *
     * @param DbModel $model Main model
     * @param array $subModels Sub-models configuration. Each sub-model should contain:<ul>
     *                         <li>name : string - Model instance name returned by this function.</li>
     *                         <li>class : string - Model class name.</li>
     *                         <li>fk : string - Foreign key attribute name. Typically for models relation [group]--<[user] FK will be group_id.</li>
     *                         <li>init : callable - Function which populate model with data. Specified function have to return array of sub-model instance. (optional)</li>
     *                         <li>scenario : string - Model validation scenario. When empty, default scenario will be used. (optional)</li>
     *                         </ul>
     * @param array|string $redirectUrl URL to which to redirect after successful models save. Url should be compatible with Url::to() method.
     * @param array $options Method options:<ul>
     *                         <li>beforeCommit : callable - Function called after successful save of all models and right before data commit. This function is ideal for various data calculations. (optional)</li>
     *                         </ul>
     * @return string|\yii\web\Response
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\UserException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws Exception
     */
    protected function createMulti($model, array $subModels, $redirectUrl = ['index'], array $options = [])
    {
        //Initial check
        array_walk($subModels, function ($item) {
            if (!array_key_exists('name', $item) || !array_key_exists('class', $item) || !array_key_exists('fk', $item)) {
                throw new InvalidConfigException(Yii::t('fts-yii-shared', 'Required sub-model parameter (name, class, fk) missing.'));
            }
        });

        //Init sub-models
        /** @var DbModel[][] $instances */
        $instances = [];
        foreach ($subModels as $subModel) {
            $className = $subModel['class'];
            if (array_key_exists('init', $subModel)) {
                if (!is_callable($subModel['init'])) {
                    throw new UserException(Yii::t('fts-yii-shared', 'Sub-model {name} init function is not callable.', ['name' => $subModel['name']]));
                }
                $instances[$subModel['name']] = call_user_func($subModel['init']);
                if (false === $instances[$subModel['name']] || !is_array($instances[$subModel['name']])) {
                    throw new UserException(Yii::t('fts-yii-shared', 'Error while populating sub-model {name}.', ['name' => $subModel['name']]));
                }
            } else {
                if (array_key_exists('scenario', $subModel)) {
                    $instances[$subModel['name']] = [new $className(['scenario' => $subModel['scenario']])];
                } else {
                    $instances[$subModel['name']] = [new $className()];
                }
            }
        }

        //Load model and sub-models
        if ($model->load(Yii::$app->request->post())) {
            foreach ($subModels as $subModel) {
                $className = $subModel['class'];
                $instances[$subModel['name']] = $model::createMultiple($className, [], array_key_exists('scenario', $subModel) ? $subModel['scenario'] : null);
                $model::loadMultiple($instances[$subModel['name']], Yii::$app->request->post());
            }

            //Ajax validation
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;

                $result = ActiveForm::validate($model);
                foreach ($subModels as $subModel) {
                    $result = ArrayHelper::merge($result, ActiveForm::validateMultiple($instances[$subModel['name']]));
                }

                return $result;
            }

            //Validate main model
            $isValid = $model->validate();

            //Save
            $useTransaction = false;
            if ($isValid) {
                try {
                    if (Yii::$app->db->getTransaction() === null) {
                        Yii::$app->db->beginTransaction();
                        $useTransaction = true;
                    }

                    if (!$model->save(false)) {
                        throw new ModelSaveException($model);
                    }

                    //Set sub-models FK ID
                    foreach ($subModels as $subModel) {
                        $fk = $subModel['fk'];
                        foreach ($instances[$subModel['name']] as $row) {
                            $row->$fk = $model->getPrimaryKey();
                        }
                    }

                    //Validate sub-models
                    foreach ($subModels as $subModel) {
                        if (!$isValid) {
                            break;
                        }
                        $validationResult = ActiveForm::validateMultiple($instances[$subModel['name']]);
                        $isValid = $isValid && 0 === count($validationResult);
                    }

                    if ($isValid) {
                        //Save sub-models
                        foreach ($subModels as $subModel) {
                            foreach ($instances[$subModel['name']] as $row) {
                                if (!$row->save(false)) {
                                    throw new ModelSaveException($row);
                                }
                            }
                        }

                        if (array_key_exists('beforeCommit', $options)) {
                            if (!is_callable($options['beforeCommit'])) {
                                throw new UserException(Yii::t('fts-yii-shared', 'beforeCommit function is not callable.'));
                            }
                            if (!call_user_func($options['beforeCommit'])) {
                                throw new UserException(Yii::t('fts-yii-shared', 'Error while calling beforeCommit function.'));
                            }
                        }
                        if ($useTransaction && ($transaction = Yii::$app->db->getTransaction()) && $transaction !== null) {
                            $transaction->commit();
                        }

                        return $this->redirect($redirectUrl);
                    } else {
                        if ($useTransaction && ($transaction = Yii::$app->db->getTransaction()) && $transaction !== null) {
                            $transaction->rollBack();
                        }
                    }
                } catch (Exception $e) {
                    if ($useTransaction && ($transaction = Yii::$app->db->getTransaction()) && $transaction !== null) {
                        $transaction->rollBack();
                    }

                    //Rethrow exception
                    throw $e;
                }
            }
        }

        $result = ['model' => $model];
        foreach ($subModels as $subModel) {
            $className = $subModel['class'];
            $result[$subModel['name']] = 0 === count($instances[$subModel['name']]) ? [new $className()] : $instances[$subModel['name']];
        }

        return $result;
    }

    /**
     * Update multiform models
     *
     * @param DbModel $model Main model
     * @param array $subModels Sub-models configuration. Each sub-model should contain:<ul>
     *                         <li>name : string - Model instance name returned by this function.</li>
     *                         <li>class : string - Model class name.</li>
     *                         <li>relation : string - Relation of model to sub-model, ie. for $model->users enter "users".</li>
     *                         <li>fk : string - Foreign key attribute name. Typically for models relation [group]--<[user] FK will be group_id.</li>
     *                         <li>sort : array - Order of sub-model items. Will be passed to the relation as part of the query. (optional)</li>
     *                         <li>scenario : string - Model validation scenario. When empty, default scenario will be used. (optional)</li>
     *                         </ul>
     * @param array|string $redirectUrl URL to which to redirect after successful models save. Url should be compatible with Url::to() method.
     * @param array $options Method options:<ul>
     *                         <li>beforeCommit : callable - Function called after successful save of all models and right before data commit. This function is ideal for various data calculations. (optional)</li>
     *                         </ul>
     * @return string|\yii\web\Response
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     */
    protected function updateMulti($model, array $subModels, $redirectUrl = ['index'], array $options = [])
    {
        //Initial check
        array_walk($subModels, function ($item) {
            if (!array_key_exists('name', $item) || !array_key_exists('relation', $item) || !array_key_exists('fk', $item) || !array_key_exists('class', $item)) {
                throw new InvalidConfigException(Yii::t('fts-yii-shared', 'Required sub-model parameter (name, class, relation, fk) missing.'));
            }
        });

        //Init sub-models
        /** @var DbModel[][] $instances */
        $instances = [];
        foreach ($subModels as $subModel) {
            $relation = 'get' . ucfirst($subModel['relation']);
            /** @var ActiveQuery $relQuery */
            $relQuery = $model->$relation();
            if (array_key_exists('sort', $subModel) && is_array($subModel['sort'])) {
                $relQuery->orderBy($subModel['sort']);
            }
            $instances[$subModel['name']] = $relQuery->all();
        }

        if ($model->load(Yii::$app->request->post())) {
            //Load sub-models data
            $instanceDeleteIds = [];
            foreach ($subModels as $subModel) {
                $fk = $subModel['fk'];
                $oldIds = ArrayHelper::map($instances[$subModel['name']], 'id', 'id');
                $instances[$subModel['name']] = DbModel::createMultiple($subModel['class'], $instances[$subModel['name']], array_key_exists('scenario', $subModel) ? $subModel['scenario'] : null);
                DbModel::loadMultiple($instances[$subModel['name']], Yii::$app->request->post());
                $instanceDeleteIds[$subModel['name']] = array_diff($oldIds, array_filter(ArrayHelper::map($instances[$subModel['name']], 'id', 'id')));

                foreach ($instances[$subModel['name']] as $row) {
                    if ($row->isNewRecord) {
                        $row->$fk = $model->id;
                    }
                }
            }

            //Ajax validation
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;

                $result = ActiveForm::validate($model);
                foreach ($subModels as $subModel) {
                    $result = ArrayHelper::merge($result, ActiveForm::validateMultiple($instances[$subModel['name']]));
                }

                return $result;
            }

            //Validate all models
            $isValid = $model->validate();
            foreach ($subModels as $subModel) {
                if (!$isValid) {
                    break;
                }

                $validationResult = ActiveForm::validateMultiple($instances[$subModel['name']]);
                $isValid = $isValid && 0 === count($validationResult);
            }

            //Save
            $useTransaction = false;
            if ($isValid) {
                try {
                    if (Yii::$app->db->getTransaction() === null) {
                        Yii::$app->db->beginTransaction();
                        $useTransaction = true;
                    }

                    if (!$model->save(false)) {
                        throw new ModelSaveException($model);
                    }
                    foreach ($subModels as $subModel) {
                        $fk = $subModel['fk'];

                        //Get table name
                        $tblNameParts = explode('\\', $subModel['class']);
                        if (is_array($tblNameParts)) {
                            $tblName = Inflector::underscore(end($tblNameParts));
                        } else {
                            $tblName = Inflector::underscore($subModel['class']);
                        }

                        //Delete rows marked to delete
                        if (0 !== count($instanceDeleteIds[$subModel['name']]) && 0 === Yii::$app->db->createCommand()->delete($tblName, ['id' => $instanceDeleteIds[$subModel['name']]])->execute()) {
                            throw new UserException(Yii::t('fts-yii-shared', 'Error while deleting rows from sub-model {name}.', ['name' => $subModel['name']]));
                        }

                        foreach ($instances[$subModel['name']] as $row) {
                            $row->$fk = $model->id;
                            if (!$row->save(false)) {
                                throw new ModelSaveException($row);
                            }
                        }
                    }

                    if (array_key_exists('beforeCommit', $options)) {
                        if (!is_callable($options['beforeCommit'])) {
                            throw new UserException(Yii::t('fts-yii-shared', 'beforeCommit function is not callable.'));
                        }
                        if (!call_user_func($options['beforeCommit'])) {
                            throw new UserException(Yii::t('fts-yii-shared', 'Error while calling beforeCommit function.'));
                        }
                    }
                    if ($useTransaction && ($transaction = Yii::$app->db->getTransaction()) && $transaction !== null) {
                        $transaction->commit();
                    }

                    return $this->redirect($redirectUrl);
                } catch (Exception $e) {
                    if ($useTransaction && ($transaction = Yii::$app->db->getTransaction()) && $transaction !== null) {
                        $transaction->rollBack();
                    }

                    //Rethrow exception
                    throw $e;
                }
            }
        }

        $result = ['model' => $model];
        foreach ($subModels as $subModel) {
            $className = $subModel['class'];
            $result[$subModel['name']] = 0 === count($instances[$subModel['name']]) ? [new $className()] : $instances[$subModel['name']];
        }

        return $result;
    }
}