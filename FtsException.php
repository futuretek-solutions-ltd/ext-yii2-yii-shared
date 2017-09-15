<?php

namespace futuretek\yii\shared;

use Exception;
use yii\base\UserException;

/**
 * Class FtsException
 * ------------------
 *
 * Exception with automatic logging
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class FtsException extends UserException
{
    /**
     * FtsException constructor.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        \Yii::error($message, 'exception');

        parent::__construct($message, $code, $previous);
    }
}