<?php

namespace futuretek\yii\shared;

use yii\base\InvalidParamException;

/**
 * Class Formatter
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Formatter extends \uniqby\phoneFormatter\i18n\Formatter
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
     * @throws \yii\base\InvalidParamException
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
     * @throws \yii\base\InvalidParamException
     */
    public function asGps($value)
    {
        return parent::asDecimal($value, 6);
    }

    /**
     * Format phone number to international format
     *
     * @param string $number Phone number
     * @return string
     * @throws \yii\base\InvalidParamException
     */
    public function asPhone($number)
    {
        $lang = explode('-', \Yii::$app->language);
        if (2 !== count($lang)) {
            throw new InvalidParamException(\Yii::t('fts-yii-shared', 'Incomplete locale. Cannot determine country code.'));
        }

        return self::asPhoneInt($number, $lang[1]);
    }
}