<?php
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
 * @date 09/06/14.06.2014 20:30
 */

namespace Mindy\Base;


/**
 * CLogRouter class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
use Mindy\Helper\Creator;

/**
 * CLogRouter manages log routes that record log messages in different media.
 *
 * For example, a file log route {@link CFileLogRoute} records log messages
 * in log files. An email log route {@link CEmailLogRoute} sends log messages
 * to specific email addresses. See {@link CLogRoute} for more details about
 * different log routes.
 *
 * Log routes may be configured in application configuration like following:
 * <pre>
 * array(
 *     'preload'=>array('log'), // preload log component when app starts
 *     'components'=>array(
 *         'log'=>array(
 *             'class'=>'CLogRouter',
 *             'routes'=>array(
 *                 array(
 *                     'class'=>'CFileLogRoute',
 *                     'levels'=>'trace, info',
 *                     'categories'=>'system.*',
 *                 ),
 *                 array(
 *                     'class'=>'CEmailLogRoute',
 *                     'levels'=>'error, warning',
 *                     'emails'=>array('admin@example.com'),
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 * </pre>
 *
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
 *
 * @property array $routes The currently initialized routes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.logging
 * @since 1.0
 */
class LogRouter extends ApplicationComponent
{
    private $_routes = array();

    /**
     * Initializes this application component.
     * This method is required by the IApplicationComponent interface.
     */
    public function init()
    {
        parent::init();
        foreach ($this->_routes as $name => $route) {
            $route = Creator::createObject($route);
            $route->init();
            $this->_routes[$name] = $route;
        }
        Mindy::getLogger()->attachEventHandler('onFlush', array($this, 'collectLogs'));
        Mindy::app()->attachEventHandler('onEndRequest', array($this, 'processLogs'));
    }

    /**
     * @return array the currently initialized routes
     */
    public function getRoutes()
    {
        return new Map($this->_routes);
    }

    /**
     * @param array $config list of route configurations. Each array element represents
     * the configuration for a single route and has the following array structure:
     * <ul>
     * <li>class: specifies the class name or alias for the route class.</li>
     * <li>name-value pairs: configure the initial property values of the route.</li>
     * </ul>
     */
    public function setRoutes($config)
    {
        foreach ($config as $name => $route)
            $this->_routes[$name] = $route;
    }

    /**
     * Collects log messages from a logger.
     * This method is an event handler to the {@link CLogger::onFlush} event.
     * @param Event $event event parameter
     */
    public function collectLogs($event)
    {
        $logger = Mindy::getLogger();
        $dumpLogs = isset($event->params['dumpLogs']) && $event->params['dumpLogs'];
        foreach ($this->_routes as $route) {
            if ($route->enabled) {
                $route->collectLogs($logger, $dumpLogs);
            }
        }
    }

    /**
     * Collects and processes log messages from a logger.
     * This method is an event handler to the {@link CApplication::onEndRequest} event.
     * @param Event $event event parameter
     * @since 1.1.0
     */
    public function processLogs($event)
    {
        $logger = Mindy::getLogger();
        foreach ($this->_routes as $route) {
            if ($route->enabled) {
                $route->collectLogs($logger, true);
            }
        }
    }
}
