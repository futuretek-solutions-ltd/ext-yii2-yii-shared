<?php

namespace futuretek\yii\shared;

use yii\base\UserException;
use yii\db\BaseActiveRecord;

/**
 * Class ModelSaveException
 * ------------------------
 *
 * Exception intended to use when handling model save
 *
 * @package futuretek\yii\shared
 * @author Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link http://www.futuretek.cz
 */
class ModelSaveException extends UserException
{
    /**
     * ModelSaveException constructor.
     *
     * @param BaseActiveRecord $model Model instance
     */
    public function __construct(BaseActiveRecord $model)
    {
        $errors = array_values($model->getFirstErrors());
        if (0 === count($errors)) {
            $message = \Yii::t(
                'fts-yii-shared',
                'Unknown error while saving data to {model}',
                ['model' => $model::className()]
            );
        } else {
            $message = \Yii::t(
                'fts-yii-shared',
                'Error while saving data to {model}: {error}',
                ['model' => $model::className(), 'error' => implode(' ', $errors)]
            );
        }

        parent::__construct($message);
    }
}