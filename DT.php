<?php

namespace futuretek\yii\shared;

use DateInterval;
use DateTime;
use DateTimeZone;
use futuretek\shared\Tools;
use yii\base\InvalidArgumentException;

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
    const DOW_MONDAY = 0;
    const DOW_TUESDAY = 1;
    const DOW_WEDNESDAY = 2;
    const DOW_THURSDAY = 3;
    const DOW_FRIDAY = 4;
    const DOW_SATURDAY = 5;
    const DOW_SUNDAY = 6;

    /**
     * Create DateTime object for specified date and time
     *
     * @param string $dateTime Date and time in valid format
     * @param string|null $timezone Timezone
     * @return DateTime
     * @throws \Exception
     * @see https://www.php.net/manual/en/timezones.php list of supported timezone codes
     */
    public static function c($dateTime = 'now', $timezone = null)
    {
        return new DateTime($dateTime, new DateTimeZone($timezone ? $timezone :  \Yii::$app->timeZone));
    }

    /**
     * Convert DateTime or string format to DB compatible format
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     * @throws \Exception
     */
    public static function toDb($dateTime = 'now')
    {
        return self::ensure($dateTime)->format('Y-m-d H:i:s');
    }

    /**
     * Convert DateTime or string format to WS compatible format
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string
     * @throws \Exception
     */
    public static function toWs($dateTime = 'now')
    {
        return self::ensure($dateTime)->format('c');
    }

    /**
     * Convert DateTime or string format to full date time with timezone information according to ISO-8601
     *
     * @param DateTime|string $dateTime Date and time in valid format or DateTime object
     * @param string $timezone Target timezone name
     * @return string
     * @throws \Exception
     * @see http://php.net/manual/en/timezones.php
     */
    public static function toTimezone($dateTime, $timezone = null)
    {
        $dt = self::ensure($dateTime);
        if ($timezone !== null && $timezone !== \Yii::$app->timeZone) {
            $dt = $dt->setTimezone(new DateTimeZone($timezone));
        }

        return $dt->format('c');
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
     * @throws \Exception
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
     * @param string|null $timezone Timezone
     * @return DateTime
     * @throws \Exception
     * @see https://www.php.net/manual/en/timezones.php list of supported timezone codes
     */
    public static function ensure($dateTime, $timezone = null)
    {
        if ($dateTime instanceof DateTime) {
            return clone $dateTime;
        }
        if (is_int($dateTime)) {
            return self::c('@' . $dateTime, $timezone);
        }

        return self::c($dateTime, $timezone);
    }

    /**
     * Display human readable date in current locale
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param string $format Display format @see yii\i18n\Formatter
     * @return string
     * @throws \yii\base\InvalidArgumentException
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
     * @throws \yii\base\InvalidArgumentException
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
     * @throws \yii\base\InvalidArgumentException
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
     * @param string $targetTimezone Target timezone name
     * @param string|null $sourceTimezone Source timezone name or application timezone when null
     * @return DateTime
     * @throws \yii\base\InvalidArgumentException
     * @throws \Exception
     * @see https://www.php.net/manual/en/timezones.php list of supported timezone codes
     */
    public static function convertTimezone($dateTime, $targetTimezone, $sourceTimezone = null)
    {
        $dt = self::ensure($dateTime, $sourceTimezone);
        if ($targetTimezone === $sourceTimezone) {
            return $dt;
        }

        return $dt->setTimezone(new DateTimeZone($targetTimezone));
    }

    /**
     * Display difference between two dates as relative time (eg. 3h 25m or 4d 17h)
     *
     * @param DateTime|string|int $start Date and time in valid format or DateTime object or timestamp(int)
     * @param DateTime|string|int $end Date and time in valid format or DateTime object or timestamp(int)
     * @param string $separator Separator. For HTML use &amp;nbsp;
     * @return string
     */
    public static function displayRelative($start, $end = 'now', $separator = ' ')
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
            $format = array_shift($format) . $separator . array_shift($format);
        } else {
            $format = array_pop($format);
        }

        return $interval->format($format);
    }

    /**
     * Convert DateInterval to seconds.
     * Can be used only on object created form DateTime::diff() method.
     *
     * @param DateInterval $interval
     * @return float|int
     */
    public static function intervalToSeconds(DateInterval $interval)
    {
        if (false === $interval->days) {
            throw new InvalidArgumentException('Method can be used only on interval created from diff.');
        }

        return $interval->days * 86400 + $interval->h * 3600 + $interval->m * 60 + $interval->s;
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

    /**
     * Returns the consecutive date ranges while respecting weekends and holidays
     *
     * @param string[]|DateTime[] $dates List of dates in suitable format (Y-m-d) or DateTime
     * @param bool $onlyWorkDays Skip weekends and holidays while detecting date blocks
     * @return \DateTime[]|null Return array with associative arrays with two DateTime elements (start, end) or null when no dates are specified
     * @throws \Exception
     */
    public static function getAllDateBlocks(array $dates, $onlyWorkDays = true)
    {
        if (0 === count($dates)) {
            return null;
        }

        array_walk($dates, function (&$val) {
            $val = substr($val, 0, 10);
        });

        $blocks = [];

        $datePeriod = new \DatePeriod(
            new \DateTime(min($dates)),
            new \DateInterval('P1D'),
            (new \DateTime(max($dates)))->add(new \DateInterval('P1D'))
        );

        $blockStart = null;
        $blockEnd = null;

        foreach ($datePeriod as $value) {
            /** @var \DateTime $value */
            if ($onlyWorkDays && (in_array(Tools::dow($value), [Tools::DOW_SATURDAY, Tools::DOW_SUNDAY], true) || Tools::isCzechHoliday($value))) {
                continue;
            }

            $date = $value->format('Y-m-d');
            if (in_array($date, $dates, true)) {
                if ($blockStart === null) {
                    $blockStart = $value;
                    $blockEnd = $value;
                } else {
                    $blockEnd = $value;
                }
            } else {
                if ($blockStart !== null) {
                    $blocks[] = [
                        'start' => $blockStart,
                        'end' => $blockEnd,
                    ];
                    $blockStart = null;
                    $blockEnd = null;
                }
            }
        }
        if ($blockStart !== null) {
            $blocks[] = [
                'start' => $blockStart,
                'end' => $blockEnd,
            ];
        }

        return $blocks;
    }

    /**
     * Whether the specified date is Czech holiday
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return bool Is holiday
     * @throws \Exception
     */
    public static function isCzechHoliday($dateTime)
    {
        $dateTime = self::ensure($dateTime);

        $holidays = ['01-01', '05-01', '05-08', '07-05', '07-06', '09-28', '10-28', '11-17', '12-24', '12-25', '12-26'];

        if (in_array($dateTime->format('m-d'), $holidays, true)) {
            return true;
        }

        //Easter
        $easterDays = easter_days($dateTime->format('Y')); //Return number of days from base to easter sunday
        $easter = self::c($dateTime->format('Y') . '-03-21');

        //Easter friday
        if (DT::add($easter, 'P' . ($easterDays - 2) . 'D')->format('Y-m-d') === $dateTime->format('Y-m-d')) {
            return true;
        }
        //Easter monday
        if (DT::add($easter, 'P' . ($easterDays + 1) . 'D')->format('Y-m-d') === $dateTime->format('Y-m-d')) {
            return true;
        }

        return false;
    }

    /**
     * Get day of week
     * Function is compatible with SQL function WEEKDAY()
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return int Day index (0 = Monday, 6 = Sunday)
     * @throws \Exception
     */
    public static function dow($dateTime = 'now')
    {
        return self::ensure($dateTime)->format('N') - 1;
    }


    /**
     * Get number of working days between two dates
     *
     * @param DateTime|string|int $dateFrom Begin date and time in valid format or DateTime object or timestamp(int)
     * @param DateTime|string|int $dateTo End date and time in valid format or DateTime object or timestamp(int)
     * @return int
     * @throws \Exception
     */
    public static function getWorkingDaysCount($dateFrom, $dateTo)
    {
        $numWorkDays = 0;
        $datePeriod = new \DatePeriod(self::ensure($dateFrom), new \DateInterval('P1D'), self::ensure($dateTo));
        foreach ($datePeriod as $value) {
            /** @var \DateTime $value */
            if (!in_array(self::dow($value), [Tools::DOW_SATURDAY, Tools::DOW_SUNDAY], true) && !self::isCzechHoliday($value)) {
                $numWorkDays++;
            }
        }

        return $numWorkDays;
    }

    /**
     * Round time up to specified minute interval
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param int $minuteInterval Minute interval to round to
     * @return \DateTime|false
     * @throws \Exception
     */
    public static function roundUpToMinuteInterval($dateTime, $minuteInterval = 10)
    {
        $dateTime = self::ensure($dateTime);

        return $dateTime->setTime(
            $dateTime->format('H'),
            ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval
        );
    }

    /**
     * Round time down to specified minute interval
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param int $minuteInterval Minute interval to round to
     * @return \DateTime
     * @throws \Exception
     */
    public static function roundDownToMinuteInterval($dateTime, $minuteInterval = 10)
    {
        $dateTime = self::ensure($dateTime);

        return $dateTime->setTime(
            $dateTime->format('H'),
            floor($dateTime->format('i') / $minuteInterval) * $minuteInterval
        );
    }

    /**
     * Round time to nearest specified minute interval
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @param int $minuteInterval Minute interval to round to
     * @return \DateTime
     * @throws \Exception
     */
    public static function roundToNearestMinuteInterval($dateTime, $minuteInterval = 10)
    {
        $dateTime = self::ensure($dateTime);

        return $dateTime->setTime(
            $dateTime->format('H'),
            round($dateTime->format('i') / $minuteInterval) * $minuteInterval
        );
    }

    /**
     * Get difference between two dates and times and output it in human readable format.
     *
     * @param DateTime|string|int $start Start date and time in valid format or DateTime object or timestamp(int)
     * @param DateTime|string|int $end End date and time in valid format or DateTime object or timestamp(int)
     *
     * @return string
     */
    public static function formatDateDiff($start, $end = 'now')
    {
        $interval = self::diff($start, $end, true);

        $format = [];
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
     * Get day name in current language
     *
     * @param DateTime|string|int $dateTime Date and time in valid format or DateTime object or timestamp(int)
     * @return string Localised day name
     * @throws \Exception
     */
    public static function getDayName($dateTime)
    {
        $dayNames = [
            self::DOW_MONDAY => \Yii::t('fts-yii-shared', 'Monday'),
            self::DOW_TUESDAY => \Yii::t('fts-yii-shared', 'Tuesday'),
            self::DOW_WEDNESDAY => \Yii::t('fts-yii-shared', 'Wednesday'),
            self::DOW_THURSDAY => \Yii::t('fts-yii-shared', 'Thursday'),
            self::DOW_FRIDAY => \Yii::t('fts-yii-shared', 'Friday'),
            self::DOW_SATURDAY => \Yii::t('fts-yii-shared', 'Saturday'),
            self::DOW_SUNDAY => \Yii::t('fts-yii-shared', 'Sunday'),
        ];

        return $dayNames[self::ensure($dateTime)->format('N') - 1];
    }

    /**
     * Check if two provided dates are the same day
     *
     * @param DateTime|string|int $date1 First date and time in valid format or DateTime object or timestamp(int)
     * @param DateTime|string|int $date2 Second date and time in valid format or DateTime object or timestamp(int)
     * @return bool
     * @throws \Exception
     */
    public static function isSameDay($date1, $date2)
    {
        $date1 = self::ensure($date1);
        $date1->setTime(0, 0);
        $date2 = self::ensure($date2);
        $date2->setTime(0, 0);

        return $date1 === $date2;
    }

    /**
     * Return number of seconds in DateInterval
     *
     * @param DateInterval $dateInterval
     * @return int Number of seconds
     * @throws \Exception
     */
    public static function toSeconds(DateInterval $dateInterval)
    {
        $reference = self::c();
        $endTime = clone $reference;
        $endTime->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * Find next occurrence of recurring date after specified date
     *
     * @param DateTime|string|int $date Date to find next occurrence of. Can be date and time in valid format or DateTime object or timestamp(int).
     * @param string|DateInterval $interval Recurring interval
     * @param DateTime|string|int $nextDate Find next occurrence after this date. Can be date and time in valid format or DateTime object or timestamp(int).
     * @return DateTime
     * @throws \Exception
     */
    public static function getNextOccurrence($date, $interval, $nextDate)
    {
        $date = self::ensure($date);
        $nextDate = self::ensure($nextDate);
        if (!($interval instanceof DateInterval)) {
            $interval = new DateInterval($interval);
        }

        while ($date <= $nextDate) {
            $date->add($interval);
        }

        return $date;
    }


    /**
     * Compare two datetimes.
     * Return:
     * <ul>
     * <li>-1 if first <i>d1</i> is less than <i>d2</i></li>
     * <li>1 if <i>d1</i> is greater than <i>d2</i></li>
     * <li>0 if they are equal</li>
     * </ul>
     *
     * @param \DateTime|int|string $d1 Date in valid format or DateTime object or timestamp(int)
     * @param \DateTime|int|string $d2 Date in valid format or DateTime object or timestamp(int)
     * @return int
     * @throws \Exception
     */
    public static function compare($d1, $d2): int
    {
        return strcmp(self::c($d1)->format('c'), self::c($d2)->format('c'));
    }
}
