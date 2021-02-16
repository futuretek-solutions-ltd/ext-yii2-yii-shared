<?php

namespace futuretek\yii\shared;

use yii\base\Model;
use yii\base\UserException;
use yii\web\Response;

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
     * @param Model $model Model instance
     */
    public function __construct(Model $model)
    {
        $errors = $model->getErrorSummary(true);
        if (0 === count($errors)) {
            $statusCode = 500;
            $message = \Yii::t(
                'fts-yii-shared',
                'Unknown error while saving data to {model}',
                ['model' => $model::className()]
            );
        } else {
            $statusCode = 422;
            $message = \Yii::t(
                'fts-yii-shared',
                'Error while saving data to {model}: {error}',
                ['model' => $model::className(), 'error' => implode(' ', $errors)]
            );
        }

        if (\Yii::$app->response instanceof Response) {
            \Yii::$app->response->setStatusCode($statusCode);
        }

        parent::__construct($message);
    }
}
