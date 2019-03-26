<?php
/**
 * Created by PhpStorm.
 * User: cerny
 * Date: 18.12.2018
 * Time: 20:54
 */

namespace futuretek\yii\shared;

use Yii;
use yii\console\Controller;

/**
 * Generate PHPStorm meta file.
 */
class MetaController extends Controller
{
    /**
     * @var string File in which the helper will be generated
     */
    public $metaFile = '@app/.phpstorm.meta.php';
    public $config = '@app/config/web.php';

    /**
     * Generate PHPStorm meta file.
     */
    public function actionIndex()
    {
        $this->stdout("Generating meta file to {$this->metaFile}\n");

        $config = require Yii::getAlias($this->config);

        file_put_contents(
            Yii::getAlias($this->metaFile),
            $this->generateAppClass($config) . $this->getGeneratePhpStormMeta($config)
        );

        $this->stdout("Done.\n");
    }

    protected function generateAppClass($config)
    {
        $property = '';
        foreach ($config as $name => $item) {
            if ($name !== 'components') {
                continue;
            }
            foreach ($item as $componentName => $data) {
                if (is_callable($data)) {
                    if ($result = $data()) {
                        $class = get_class($result);
                    }
                } else {
                    $class = isset($data['class']) ? $data['class'] : false;
                }

                if (!isset($class) || !$class) {
                    continue;
                }
                $property .= "\t/** @var $class \$$componentName */\n\tpublic \$$componentName;\n";
            }
        }

        $template = '<?php

class Yii {
    /** @var App $app */
    public static $app;
}

class App {
     ' . $property . '
}';

        return $template;
    }

    public function getGeneratePhpStormMeta($config)
    {
        $code = [];
        foreach ($config as $name => $item) {
            if ($name !== 'modules') {
                continue;
            }
            foreach ($item as $moduleName => $params) {
                $code[] = "\t\t\"$moduleName\" => {$params['class']}::class";
            }
        }

        $template = '
        
namespace PHPSTORM_META {
    override(\yii\base\Module::getModule(0), map([
' . implode(", \n", $code) . '
    ]));     
}';

        return $template;
    }
}