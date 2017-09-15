<?php

namespace futuretek\yii\shared;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class DbModel
 * -------------
 * Parent for ActiveRecord database models.
 *
 * Extend ActiveRecord with following features:
 * * Automatic population of created_at and updated_at fields, if present in model
 * * Model validation logging - add item `logModelValidationErrors => true` to `Yii::$app->params`
 * * Introduce method for populating multiple models
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 *
 * @property string $created_at Record creation date and time
 * @property string $updated_at Record update date and time
 */
class DbModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord() && $this->hasAttribute('created_at')) {
            $this->created_at = (new \DateTime('now', new \DateTimeZone(Yii::$app->timeZone)))->format('Y-m-d H:i:s');
        }
        if ($this->hasAttribute('updated_at')) {
            $this->updated_at = (new \DateTime('now', new \DateTimeZone(Yii::$app->timeZone)))->format('Y-m-d H:i:s');
        }

        return parent::save($runValidation, $attributeNames);
    }

    /**
     * @inheritdoc
     */
    public function afterValidate()
    {
        if (array_key_exists('logModelValidationErrors', Yii::$app->params) && Yii::$app->params['logModelValidationErrors'] && $this->hasErrors()) {
            Yii::warning(
                sprintf(
                    "Model validation failed - %s\n\nErrors: %s\n\nValues: %s",
                    self::className(),
                    var_export($this->errors, true),
                    var_export($this->toArray(), true)
                ),
                'validation'
            );
        }

        parent::afterValidate();
    }

    /**
     * Creates and populates a set of models.
     *
     * @param string $modelClass
     * @param array $multipleModels
     * @param string $scenario Optional model scenario
     * @return array
     */
    public static function createMultiple($modelClass, array $multipleModels = [], $scenario = null)
    {
        /** @var self $model */
        $model = new $modelClass();
        if ($scenario !== null) {
            $model->setScenario($scenario);
        }
        $formName = $model->formName();
        $post = Yii::$app->request->post($formName);
        $models = [];

        if (0 !== count($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, 'id', 'id'));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (array_key_exists('id', $item) && !empty($item['id']) && array_key_exists($item['id'], $multipleModels)) {
                    $tmpModel = $multipleModels[$item['id']];
                    if ($scenario !== null) {
                        $tmpModel->setScenario($scenario);
                    }
                    $models[] = $tmpModel;
                } else {
                    $models[] = $scenario !== null ? new $modelClass(['scenario' => $scenario]) : new $modelClass();
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }
}