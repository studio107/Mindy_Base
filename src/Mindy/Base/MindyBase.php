<?php

namespace Mindy\Base;

use Mindy\Base\Compatability\CompatabilityLayer;
use Mindy\Base\Exception\Exception;
use Mindy\Helper\Alias;
use Mindy\Helper\Console;


/**
 * YiiBase class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @package system
 * @since 1.0
 */

/**
 * Gets the application start timestamp.
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME', microtime(true));
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
/**
 * This constant defines how much call stack information (file name and line number) should be logged by Yii::trace().
 * Defaults to 0, meaning no backtrace information. If it is greater than 0,
 * at most that number of call stacks will be logged. Note, only user application call stacks are considered.
 */
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 0);
/**
 * This constant defines whether exception handling should be enabled. Defaults to true.
 */
defined('YII_ENABLE_EXCEPTION_HANDLER') or define('YII_ENABLE_EXCEPTION_HANDLER', true);
/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);
/**
 * Defines the Yii framework installation path.
 */
defined('YII_PATH') or define('YII_PATH', dirname(__FILE__));
/**
 * Defines the Zii library installation path.
 */
defined('YII_ZII_PATH') or define('YII_ZII_PATH', YII_PATH . DIRECTORY_SEPARATOR . 'zii');

/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/06/14.06.2014 18:01
 */
abstract class MindyBase
{
    use CompatabilityLayer;

    private static $_app;

    private static $_logger;

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return \Mindy\Base\App\Application the application singleton, null if the singleton has not been created yet.
     */
    public static function app()
    {
        return self::$_app;
    }

    /**
     * Stores the application instance in the class static member.
     * This method helps implement a singleton pattern for CApplication.
     * Repeated invocation of this method or the CApplication constructor
     * will cause the throw of an exception.
     * To retrieve the application instance, use {@link app()}.
     * @param \Mindy\Base\App\Application $app the application instance. If this is null, the existing
     * application singleton will be removed.
     * @throws Exception if multiple application instances are registered.
     */
    public static function setApplication($app)
    {
        if (self::$_app === null || $app === null) {
            self::$_app = $app;
        } else {
            throw new Exception(Mindy::t('yii', 'Yii application can only be created once.'));
        }
    }

    /**
     * Translates a message to the specified language.
     * This method supports choice format (see {@link CChoiceFormat}),
     * i.e., the message returned will be chosen from a few candidates according to the given
     * number value. This feature is mainly used to solve plural format issue in case
     * a message has different plural forms in some languages.
     * @param string $category message category. Please use only word letters. Note, category 'yii' is
     * reserved for Yii framework core code use. See {@link CPhpMessageSource} for
     * more interpretation about message category.
     * @param string $message the original message
     * @param array $params parameters to be applied to the message using <code>strtr</code>.
     * The first parameter can be a number without key.
     * And in this case, the method will call {@link CChoiceFormat::format} to choose
     * an appropriate message translation.
     * Starting from version 1.1.6 you can pass parameter for {@link CChoiceFormat::format}
     * or plural forms format without wrapping it with array.
     * This parameter is then available as <code>{n}</code> in the message translation string.
     * @param string $source which message source application component to use.
     * Defaults to null, meaning using 'coreMessages' for messages belonging to
     * the 'yii' category and using 'messages' for the rest messages.
     * @param string $language the target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
     * @return string the translated message
     * @see CMessageSource
     */
    public static function t($category, $message, $params = array(), $source = null, $language = null)
    {
        if (self::$_app !== null) {
            if ($source === null) {
                $source = ($category === 'yii' || $category === 'zii') ? 'coreMessages' : 'messages';
            }

            if (($source = self::$_app->getComponent($source)) !== null) {
                /* @var $source \Mindy\Base\MessageSource */
                $message = $source->translate($category, $message, $language);
            }
        }

        if ($params === []) {
            return $message;
        }

        if (!is_array($params))
            $params = [$params];

        if (isset($params[0])) { // number choice
            if (strpos($message, '|') !== false) {
                if (strpos($message, '#') === false) {
                    $chunks = explode('|', $message);
                    $expressions = self::$_app->getLocale($language)->getPluralRules();
                    if ($n = min(count($chunks), count($expressions))) {
                        for ($i = 0; $i < $n; $i++) {
                            $chunks[$i] = $expressions[$i] . '#' . $chunks[$i];
                        }

                        $message = implode('|', $chunks);
                    }
                }
                $message = ChoiceFormat::format($message, $params[0]);
            }
            if (!isset($params['{n}'])) {
                $params['{n}'] = $params[0];
            }
            unset($params[0]);
        }
        return $params !== [] ? strtr($message, $params) : $message;
    }

    /**
     * Creates an application of the specified class.
     * @param string $class the application class name
     * @param mixed $config application configuration. This parameter will be passed as the parameter
     * to the constructor of the application class.
     * @return mixed the application instance
     */
    protected static function createApplication($class, $config = null)
    {
        return new $class($config);
    }

    /**
     * Creates a Web application instance.
     * @param mixed $config application configuration.
     * If a string, it is treated as the path of the file that contains the configuration;
     * If an array, it is the actual configuration information.
     * Please make sure you specify the {@link CApplication::basePath basePath} property in the configuration,
     * which should point to the directory containing all application logic, template and data.
     * If not, the directory will be defaulted to 'protected'.
     * @param string $className
     * @return \Mindy\Base\App\Application
     */
    public static function getInstance($config = null, $className = '\Mindy\Base\App\Application')
    {
        foreach (['system' => YII_PATH, 'zii' => YII_ZII_PATH] as $name => $path) {
            Alias::set($name, $path);
        }

        $app = self::createApplication($className, $config);
        if (Console::isCli()) {
            // fix for fcgi
            defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
            $app->commandRunner->addCommands(YII_PATH . '/cli/commands');

            $env = @getenv('YII_CONSOLE_COMMANDS');
            if (!empty($env)) {
                $app->commandRunner->addCommands($env);
            }

            foreach ($app->modules as $name => $settings) {
                if ($modulePath = Alias::get("application.modules." . $name)) {
                    $app->commandRunner->addCommands($modulePath . DIRECTORY_SEPARATOR . 'commands');
                }
            }
        }
        return $app;
    }

    /**
     * Writes a trace message.
     * This method will only log a message when the application is in debug mode.
     * @param string $msg message to be logged
     * @param string $category category of the message
     * @see log
     */
    public static function trace($msg, $category = 'application')
    {
//        if (YII_DEBUG)
//            self::log($msg, CLogger::LEVEL_TRACE, $category);
    }

    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null)
            return self::$_logger;
        else
            return self::$_logger = new Logger;
    }
}