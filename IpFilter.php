<?php

namespace futuretek\yii\shared;

use yii\base\Behavior;
use yii\base\Exception;
use yii\web\Controller;
use futuretek\shared\Tools as SharedTools;
use yii\web\ForbiddenHttpException;

/**
 * IpFilter is an filter that allow/deny requests by IP addresses or range.
 *
 * To use IpFilter, declare it in the `behaviors()` method of your controller class.
 *
 * ```
 * public function behaviors()
 * {
 *     return [
 *         'ips' => [
 *             'class' => \futuretek\api\IpFilter::className(),
 *             'policy' => IpFilter::POLICY_ALLOW_ALL,
 *             'list' => [
 *                 '192.168.1.2',
 *                 '192.168.11.1-192.168.11.27',
 *                 '10.*.*.*',
 *                 '172.16.1.0/24',
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class IpFilter extends Behavior
{
    /**
     * Allow all IPs except specified ones
     */
    const POLICY_ALLOW_ALL = 1;

    /**
     * Deny all IPs except specified ones
     */
    const POLICY_DENY_ALL = 2;

    /**
     * @var int IP filtering policy (default: allow all except list)
     */
    public $policy = self::POLICY_ALLOW_ALL;

    /**
     * @var array List of IP address exceptions. For example:
     *            <ul>
     *            <li>192.168.11.208</li>
     *            <li>192.168.1.24-192.168.1.102</li>
     *            <li>192.168.24.*</li>
     *            <li>172.16.0.0/16</li>
     *            </ul>
     */
    public $list = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * @inheritdoc
     * @throws \yii\web\ForbiddenHttpException
     */
    public function beforeAction()
    {
        $inList = false;
        foreach ($this->list as $ipRange) {
            $inList = $inList || SharedTools::isIpInRange(SharedTools::getRemoteAddr(), $ipRange);
        }

        if (($this->policy === self::POLICY_ALLOW_ALL && $inList) || ($this->policy === self::POLICY_DENY_ALL && !$inList)) {
            throw new ForbiddenHttpException('Your IP address is not allowed');
        }

        return true;
    }
}
