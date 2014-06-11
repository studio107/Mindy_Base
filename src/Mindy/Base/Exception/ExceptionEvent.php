<?php

namespace Mindy\Base\Exception;

/**
 * CExceptionEvent class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
use Mindy\Base\Event;

/**
 * CExceptionEvent represents the parameter for the {@link CApplication::onException onException} event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
class ExceptionEvent extends Event
{
    /**
     * @var Exception the exception that this event is about.
     */
    public $exception;

    /**
     * Constructor.
     * @param mixed $sender sender of the event
     * @param Exception $exception the exception
     */
    public function __construct($sender, $exception)
    {
        $this->exception = $exception;
        parent::__construct($sender);
    }
}