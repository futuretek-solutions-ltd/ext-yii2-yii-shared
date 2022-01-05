<?php

namespace futuretek\yii\shared;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use yii\base\InvalidArgumentException;

/**
 * Class Formatter
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Formatter extends \yii\i18n\Formatter
{
    /** @var string Distance unit sign (km, mi, ...) */
    public $distanceUnits = 'km';

    /** @var int Distance fraction digits */
    public $distanceFractionDigits = 3;

    /**
     * Format value as distance
     *
     * @param float|int $value The value to be formatted
     * @return string
     * @throws InvalidArgumentException
     */
    public function asDistance($value)
    {
        if ($value === null) {
            return $this->nullDisplay;
        }

        return number_format($this->normalizeNumericValue($value), $this->distanceFractionDigits, $this->decimalSeparator, $this->thousandSeparator) . ' ' . $this->distanceUnits;
    }

    /**
     * Formats the value as a GPS coordinate.
     *
     * @param mixed $value the value to be formatted.
     * @return string the formatted result.
     * @throws InvalidArgumentException
     */
    public function asGps($value)
    {
        return $this->asDecimal($value, 6);
    }

    /**
     * Format phone number to international format
     *
     * @param string $number Phone number
     * @return string
     * @throws InvalidArgumentException
     */
    public function asPhone($number)
    {
        $lang = explode('-', \Yii::$app->language);
        if (2 !== count($lang)) {
            throw new InvalidArgumentException(\Yii::t('fts-yii-shared', 'Incomplete locale. Cannot determine country code.'));
        }

        return self::asPhoneInt($number, $lang[1]);
    }
    /**
     * Converts phone to E164 format
     *
     * @param string $phone
     * @param $defaultRegionAlpha2
     * @return String
     */
    public static function asPhoneE164($phone, $defaultRegionAlpha2)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parseAndKeepRawInput($phone, $defaultRegionAlpha2);
            return $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return $phone;
        }
    }

    /**
     * Converts phone number to international format
     *
     * @param string $phone
     * @param $defaultRegionAlpha2
     * @return String
     */
    public static function asPhoneInt($phone, $defaultRegionAlpha2)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parseAndKeepRawInput($phone, $defaultRegionAlpha2);
            return $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException $e) {
            return $phone;
        }
    }
}
