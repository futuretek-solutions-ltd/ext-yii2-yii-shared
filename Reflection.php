<?php

namespace futuretek\yii\shared;

use yii\helpers\Inflector;

/**
 * Class Reflection
 *
 * @package futuretek\yii\shared
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Reflection
{
    /**
     * Get controller actions
     *
     * @param string $controllerClass Controller class name
     * @return array
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    public static function getControllerActions($controllerClass)
    {
        $methods = [];

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException(\Yii::t('fts-yii-shared', 'Specified class {class} not found.', ['class' => $controllerClass]));
        }
        /** @var \yii\web\Controller $class */
        $class = new $controllerClass('id', null);

        //External actions
        foreach ($class->actions() as $name => $action) {
            if (is_array($action)) {
                $action = $action['class'];
            }
            if (!class_exists($action) || !method_exists($action, 'run')) {
                continue;
            }
            $reflection = new \ReflectionMethod($action, 'run');
            if ($reflection->isPublic()) {
                $methods[] = $name;
            }
        }

        //Inline actions
        $reflection = new \ReflectionClass($controllerClass);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->getName() !== 'actions' && 0 === strpos($method->getName(), 'action')) {
                $methods[] = Inflector::camel2id(substr($method->getName(), 6));
            }
        }

        sort($methods);

        return $methods;
    }

    /**
     * Get array of controller URLs with actions URLs
     *
     * @param bool $modules Include controllers within modules
     * @return string[][]
     * @throws \yii\base\InvalidParamException
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public static function getControllerUrlsWithActions($modules = true)
    {
        $controllers = [];

        $controllerClasses = self::getControllersInPath();
        if ($modules) {
            $controllerClasses = array_merge($controllerClasses, self::getControllersInPath('@app/modules'));
        }

        foreach ($controllerClasses as $class) {
            $name = self::controllerUrlFromClass($class);
            $controllers[$name] = self::getControllerActions($class);
        }

        return $controllers;
    }

    /**
     * Get controller URL address from controller class name
     *
     * @param string $className Controller class name
     * @return string
     */
    public static function controllerUrlFromClass($className)
    {
        if (0 === strpos($className, 'app')) {
            $className = substr($className, 3);
        }
        $className = str_replace('\\', '/', $className);
        $parts = explode('/', $className);
        array_walk($parts, function (&$value) {
            $value = Inflector::camel2id($value);
        });

        $implode = implode('/', $parts);

        return str_replace(['controllers/', '-controller', 'modules/'], '', $implode);
    }

    /**
     * Get all controllers in path
     *
     * @param string $path Path where to search for controllers
     * @return string[]
     * @throws \yii\base\InvalidParamException
     */
    public static function getControllersInPath($path = null)
    {
        if ($path === null) {
            $path = \Yii::$app->controllerPath;
        }

        $path = \Yii::getAlias($path);

        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+Controller\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $controllers = [];
        foreach ($regex as $file) {
            if (is_array($file)) {
                $file = reset($file);
            }
            $file = ltrim(str_replace([\Yii::$app->basePath, '.php'], '', $file), '/\\');

            $controllers[] = 'app\\' . $file;
        }

        return $controllers;
    }
}