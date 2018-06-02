<?php

namespace futuretek\yii\shared;

/**
 * Class CnbRates
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license https://www.apache.org/licenses/LICENSE-2.0.html Apache-2.0
 * @link    http://www.futuretek.cz
 */
class CnbRates
{
    const URL = 'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';

    /**
     * Get exchange rates from CNB
     *
     * @param string|\DateTime|int|null $date Exchange rates date
     * @return CnbRate[]
     * @throws \RuntimeException
     */
    public static function getRates($date = null)
    {
        $dateStr = $date === null ? DT::c()->format('d.m.Y') : DT::c($date)->format('d.m.Y');

        return \Yii::$app->cache->getOrSet([__NAMESPACE__, __CLASS__, 'rates', $dateStr], function () use ($date, $dateStr) {
            $dateParam = '';
            if ($date !== null) {
                $dateParam = '?date=' . $dateStr;
            }

            $data = file_get_contents(self::URL . $dateParam);
            if (false === $data) {
                throw new \RuntimeException('Error while getting exchange rates from CNB.');
            }

            $lines = explode("\n", $data);
            array_shift($lines);
            array_shift($lines);
            $result = [];
            $obj = new CnbRate();
            foreach ($lines as $line) {
                $part = explode('|', $line);
                $obj->country = $part[0];
                $obj->name = $part[1];
                $obj->conversionBase = (float)$part[2];
                $obj->code = $part[3];
                $obj->conversionRate = (float)str_replace(',', '.', $part[4]);
                $result[$obj->code] = clone $obj;
            }

            return $result;
        });
    }
}

/**
 * Class CnbRate
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license https://www.apache.org/licenses/LICENSE-2.0.html Apache-2.0
 * @link    http://www.futuretek.cz
 */
class CnbRate
{
    /** @var string Currency country */
    public $country;
    /** @var string Currency name */
    public $name;
    /** @var string Currency ISO code */
    public $code;
    /** @var double Cconversion rate to CZK */
    public $conversionRate;
    /** @var double Conversion base */
    public $conversionBase;
}