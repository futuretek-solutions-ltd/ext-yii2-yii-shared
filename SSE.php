<?php

namespace futuretek\yii\shared;

use yii\base\InvalidParamException;
use yii\helpers\Json;

/**
 * Class SSE
 * ---------
 *
 * Provides server sent events (SSE) support.
 *
 * Usage:
 * ```
 * (new SSE)
 *     ->setMaxLoopTime(60)
 *     ->setSleepTime(10)
 *     ->addJob('randomNumber', function() {
 *         return rand();
 *     })->addJob('timeArray', function() {
 *         return ['date' => date('Y-m-d'), 'time' => date('H:i:s')];
 *     })->run();
 * ```
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@marbes.cz>
 * @license Apache-2.0
 * @link    http://www.marbes.cz
 */
class SSE
{
    /**
     * @var int Maximal SSE loop run (in seconds)
     */
    private $_maxLoopTime;
    /**
     * @var int Sleep time between job invocation (in seconds)
     */
    private $_sleepTime;
    /**
     * @var int The time client tries to reconnect after connection has been lost (in seconds)
     */
    private $_retryTime;
    /**
     * @var callable[] SSE Jobs
     */
    private $_jobs;
    /**
     * @var bool Stop flag
     */
    private $_stop;

    /**
     * SSE constructor.
     */
    public function __construct()
    {
        $this->_maxLoopTime = 15;
        $this->_sleepTime = 5;
        $this->_retryTime = 1;
        $this->_jobs = [];
        $this->_stop = false;
    }

    /**
     * Add job to SSE
     *
     * Job is defined as callable - eg. anonymous function.
     * * Function input is sse instance ($this)
     * * Function return value should be:
     *   * array - will be converted to JSON
     *   * string - will be sent AS IS
     *
     * @param string $name Job name
     * @param callable $function Job callable
     * @return $this
     * @throws \yii\base\InvalidParamException
     */
    public function addJob($name, callable $function)
    {
        if (!is_callable($function)) {
            throw new InvalidParamException("Job with name {$name} is not callable.");
        }
        $this->_jobs[$name] = $function;

        return $this;
    }

    /**
     * Remove job from SSE
     *
     * @param string $name Job name
     * @return $this
     */
    public function removeJob($name)
    {
        if (array_key_exists($name, $this->_jobs)) {
            unset($this->_jobs[$name]);
        }

        return $this;
    }

    /**
     * Run SSE job
     *
     * @throws \yii\base\InvalidParamException
     */
    public function run()
    {
        $this->_init();
        $time = DT::c();

        //Send retry interval
        $this->_retry($this->_retryTime * 1000);
        while (DT::diff('now', $time, true)->s < $this->_maxLoopTime) {
            if (connection_aborted() === 1) {
                exit;
            }

            foreach ($this->_jobs as $name => $func) {
                $this->_sendEvent($name, $func($this));
                if ($this->_stop) {
                    flush();
                    exit;
                }
            }

            flush();
            sleep($this->_sleepTime);
        }
        exit;
    }

    /**
     * Set maximal SSE loop run (in seconds)
     *
     * @param int $maxLoopTime
     * @return $this
     */
    public function setMaxLoopTime($maxLoopTime)
    {
        $this->_maxLoopTime = $maxLoopTime;

        return $this;
    }

    /**
     * Set sleep time between job invocation (in seconds)
     *
     * @param int $sleepTime
     * @return $this
     */
    public function setSleepTime($sleepTime)
    {
        $this->_sleepTime = $sleepTime;

        return $this;
    }

    /**
     * Set the time client tries to reconnect after connection has been lost (in seconds)
     *
     * @param int $retryTime
     * @return $this
     */
    public function setRetryTime($retryTime)
    {
        $this->_retryTime = $retryTime;

        return $this;
    }

    /**
     * Stop the SSE loop after sending current job
     */
    public function setStop()
    {
        $this->_stop = true;
    }

    /**
     * Send SSE event
     *
     * @param string $name Event name
     * @param mixed $data Event data
     * @param mixed $id Event ID
     * @throws \yii\base\InvalidParamException
     */
    private function _sendEvent($name, $data = null, $id = null)
    {
        if ($data === null) {
            echo "\n";

            return;
        }

        if ($id === null) {
            $id = \futuretek\shared\Tools::GUIDv4();
        }

        echo "event: {$name}\nid: {$id}\n";

        if (is_array($data)) {
            $data = Json::encode($data);
        }

        $messages = explode("\n", $data);
        foreach ($messages as $message) {
            echo "data: {$message}\n";
        }

        echo "\n";
    }

    /**
     * Send SSE retry message
     *
     * @param int $time Retry time in miliseconds
     */
    private function _retry($time)
    {
        echo "retry: {$time}\n\n";
    }

    /**
     * Init SSE engine
     */
    private function _init()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');

        @ini_set('output_buffering', 0);
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        @ob_end_clean();

        set_time_limit(0);
        ignore_user_abort(false);
    }
}