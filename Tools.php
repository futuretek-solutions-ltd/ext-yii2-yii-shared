<?php

namespace futuretek\yii\shared;

/**
 * Class Tools
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Tools
{
    /**
     * Write Yii's param config file
     *
     * @param array $data Associative array with key-value pairs
     * @param bool $merge Merge with original file content
     * @return bool Operation result
     */
    public static function writeYiiParams(array $data, $merge = false)
    {
        if ($merge !== false) {
            $data = array_replace_recursive(\Yii::$app->params, $data);
        }
        $fileName = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params.php';

        return (is_writable($fileName) && ($fp = fopen($fileName, 'wb')) !== false && fwrite($fp, "<?php\n\nreturn " . var_export($data, true) . ";\n") && fclose($fp));
    }
}
