<?php

namespace futuretek\yii\shared;

use DateInterval;
use DateTime;
use DateTimeZone;
use futuretek\shared\Tools;

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
    public static function toDb($dateTime = 'now')
    {
        return self::ensure($dateTime)->format('Y-m-d H:i:s');
    }

    /**
     * Convert DateTime or string format to full date time with timezone information according to ISO-8601
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     */
    public static function toTimezone($dateTime = 'now')
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

    /**
     * Display difference between two dates as relative time (eg. 3h 25m or 4d 17h)
     *
     * @param DateTime|string|int $start Date and time in valid format or DateTime object or timestamp(int)
     * @param DateTime|string|int $end Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     */
    public static function displayRelative($start, $end = 'now')
    {
        $format = [];
        $interval = self::diff($start, $end, true);

        if ($interval->y !== 0) {
            $format[] = '%yR';
        }
        if ($interval->m !== 0) {
            $format[] = '%mM';
        }
        if ($interval->d !== 0) {
            $format[] = '%dD';
        }
        if ($interval->h !== 0) {
            $format[] = '%hh';
        }
        if ($interval->i !== 0) {
            $format[] = '%im';
        }
        if ($interval->s !== 0) {
            $format[] = '%ss';
        }

        // We use the two biggest parts
        if (0 === count($format)) {
            return \Yii::t('fts-yii-shared', 'now');
        }
        if (count($format) > 1) {
            $format = array_shift($format) . '&nbsp;' . array_shift($format);
        } else {
            $format = array_pop($format);
        }

        return $interval->format($format);
    }

    /**
     * Returns the first consecutive date range while respecting weekends and holidays
     *
     * @param string[]|DateTime[] $dates List of dates in suitable format (Y-m-d) or DateTime
     * @param bool $onlyWorkDays Skip weekends and holidays while detecting date blocks
     * @return \DateTime[]|null Return associative array with two DateTime elements (start, end) or null when no dates are specified
     * @throws \Exception
     */
    public static function getFirstDateBlock(array $dates, $onlyWorkDays = true)
    {
        if (0 === count($dates)) {
            return null;
        }

        array_walk($dates, function (&$val) {
            $val = $val instanceof DateTime ? $val->format('Y-m-d') : substr($val, 0, 10);
        });

        $startDate = new \DateTime(min($dates));
        $endDate = clone $startDate;
        $datePeriod = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            (new \DateTime(max($dates)))->add(new \DateInterval('P1D'))
        );
        foreach ($datePeriod as $value) {
            /** @var \DateTime $value */
            if ($onlyWorkDays && (in_array(Tools::dow($value), [Tools::DOW_SATURDAY, Tools::DOW_SUNDAY], true) || Tools::isCzechHoliday($value))) {
                continue;
            }

            $date = $value->format('Y-m-d');
            if (!in_array($date, $dates, true)) {
                break;
            }
            $endDate = clone $value;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }
}