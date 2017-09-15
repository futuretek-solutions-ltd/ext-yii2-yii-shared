<?php

namespace futuretek\yii\shared;

use Yii;
use yii\web\AssetManager;
use yii\web\AssetBundle;

/**
 * Class TestAssetManager
 * ----------------------
 *
 * This asset manager class is used in functional tests to prevent asset publishing.
 *
 * Usage:
 * ```
 * 'components' => [
 *     'assetManager' => [
 *         'class' => 'futuretek\yii\shared\TestAssetManager',
 *     ],
 * ],
 * ```
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class TestAssetManager extends AssetManager
{
    /**
     * @inheritdoc
     */
    public function getBundle($name, $publish = true)
    {
        if (!class_exists($name)) {
            $name = AssetBundle::class;
        }

        return Yii::createObject($name);
    }

    /**
     * @inheritdoc
     */
    public function publish($path, $options = [])
    {
        return [null, null];
    }
}