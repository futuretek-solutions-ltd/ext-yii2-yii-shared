<?php

namespace futuretek\yii\shared;

use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Class DT
 * Helper class for date time operations
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class DT
{
    /**
     * Create DateTime object for specified date and time
     *
     * @param string $dateTime Date and time in valid format
     * @return DateTime
     */
    public static function c($dateTime = 'now')
    {
        return new DateTime($dateTime, new DateTimeZone(\Yii::$app->timeZone));
    }

    /**
     * Convert DateTime or string format to DB compatible format
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     */
    public static function toDb($dateTime)
    {
        return self::ensure($dateTime)->format('Y-m-d H:i:s');
    }

    /**
     * Convert DateTime or string format to full date time with timezone information according to ISO-8601
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     */
    public static function toTimezone($dateTime)
    {
        return self::ensure($dateTime)->format('c');
    }

    /**
     * Add specified interval to DateTime
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $interval Valid date interval eg. P2DT3H
     * @return DateTime
     * @throws \Exception
     */
    public static function add($dateTime, $interval)
    {
        return self::ensure($dateTime)->add(new DateInterval($interval));
    }

    /**
     * Subtract specified interval from DateTime
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $interval Valid date interval eg. P2DT3H
     * @return DateTime
     * @throws \Exception
     */
    public static function sub($dateTime, $interval)
    {
        return self::ensure($dateTime)->sub(new DateInterval($interval));
    }

    /**
     * Get difference between two dates
     *
     * @param DateTime|string $d1 Date 1
     * @param DateTime|string $d2 Date 2
     * @param bool $absolute Return absolute difference (always positive)
     * @return bool|DateInterval DateInterval or false on error
     */
    public static function diff($d1, $d2, $absolute = false)
    {
        return self::ensure($d1)->diff(self::ensure($d2), $absolute);
    }

    /**
     * Get date of specified day of week in current week.
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param int $dayOfWeek Desired day of week (1 = Monday, 7 = Sunday)
     * @param int $weekOffset Add or subtract weeks from returned date. Positive number add weeks, negative subtract weeks.
     * @return DateTime
     * @throws \Exception
     */
    public static function getDayOfWeek($dateTime, $dayOfWeek, $weekOffset = 0)
    {
        $dateTime = self::ensure($dateTime);
        $offset = ((int)$dateTime->format('N') - (int)$dayOfWeek) + ($weekOffset * 7);
        if ($offset < 0) {
            return self::add($dateTime, 'P' . abs($offset) . 'D');
        }
        if ($offset > 0) {
            return self::sub($dateTime, 'P' . abs($offset) . 'D');
        }

        return $dateTime;
    }

    /**
     * Ensure that parameter is DateTime object and that this object is cloned
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return DateTime
     */
    public static function ensure($dateTime)
    {
        if ($dateTime instanceof DateTime) {
            return clone $dateTime;
        }
        if (is_int($dateTime)) {
            return self::c('@' . $dateTime);
        }

        return self::c($dateTime);
    }

    /**
     * Display human readable date in current locale
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $format Display format @see yii\i18n\Formatter
     * @return string
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     */
    public static function displayDate($dateTime, $format = null)
    {
        return \Yii::$app->formatter->asDate($dateTime, $format);
    }

    /**
     * Display human readable date and time in current locale
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $format Display format @see yii\i18n\Formatter
     * @return string
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     */
    public static function displayDateTime($dateTime, $format = null)
    {
        return \Yii::$app->formatter->asDatetime($dateTime, $format);
    }

    /**
     * Display human readable time in current locale
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $format Display format @see yii\i18n\Formatter
     * @return string
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     */
    public static function displayTime($dateTime, $format = null)
    {
        return \Yii::$app->formatter->asTime($dateTime, $format);
    }

    /**
     * Convert datetime to different timezone
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $timezone Target timezone name
     * @return DateTime
     * @see http://php.net/manual/en/timezones.php
     * @throws \yii\base\InvalidParamException
     * @throws \yii\base\InvalidConfigException
     */
    public static function convertTimezone($dateTime, $timezone)
    {
        $dt = self::ensure($dateTime);
        if ($timezone === \Yii::$app->timeZone) {
            return $dt;
        }

        return $dt->setTimezone(new DateTimeZone($timezone));
    }
}