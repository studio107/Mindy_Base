<?php

namespace Mindy\Base;

use Mindy\Base\App\Application;
use Mindy\Base\Exception\Exception;
use Mindy\Base\Exception\ExceptionEvent;
use Mindy\Base\Exception\HttpException;

/**
 * CErrorHandler handles uncaught PHP errors and exceptions.
 *
 * It displays these errors using appropriate views based on the
 * nature of the error and the mode the application runs at.
 * It also chooses the most preferred language for displaying the error.
 *
 * CErrorHandler uses two sets of views:
 * <ul>
 * <li>development views, named as <code>exception.php</code>;
 * <li>production views, named as <code>error&lt;StatusCode&gt;.php</code>;
 * </ul>
 * where &lt;StatusCode&gt; stands for the HTTP error code (e.g. error500.php).
 * Localized views are named similarly but located under a subdirectory
 * whose name is the language code (e.g. zh_cn/error500.php).
 *
 * Development views are displayed when the application is in debug mode
 * (i.e. YII_DEBUG is defined as true). Detailed error information with source code
 * are displayed in these views. Production views are meant to be shown
 * to end-users and are used when the application is in production mode.
 * For security reasons, they only display the error message without any
 * sensitive information.
 *
 * CErrorHandler looks for the view templates from the following locations in order:
 * <ol>
 * <li><code>themes/ThemeName/views/system</code>: when a theme is active.</li>
 * <li><code>protected/views/system</code></li>
 * <li><code>framework/views</code></li>
 * </ol>
 * If the view is not found in a directory, it will be looked for in the next directory.
 *
 * The property {@link maxSourceLines} can be changed to specify the number
 * of source code lines to be displayed in development views.
 *
 * CErrorHandler is a core application component that can be accessed via
 * {@link CApplication::getErrorHandler()}.
 *
 * @property array $error The error details. Null if there is no error.
 * @property Exception|null $exception exception instance. Null if there is no exception.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
class ErrorHandler extends ApplicationComponent
{
    /**
     * @var integer maximum number of source code lines to be displayed. Defaults to 25.
     */
    public $maxSourceLines = 35;

    /**
     * @var integer maximum number of trace source code lines to be displayed. Defaults to 10.
     * @since 1.1.6
     */
    public $maxTraceSourceLines = 10;

    /**
     * @var string the application administrator information (could be a name or email link). It is displayed in error pages to end users. Defaults to 'the webmaster'.
     */
    public $adminInfo = 'the webmaster';
    /**
     * @var boolean whether to discard any existing page output before error display. Defaults to true.
     */
    public $discardOutput = true;
    /**
     * @var string the route (eg 'site/error') to the controller action that will be used to display external errors.
     * Inside the action, it can retrieve the error information by Yii::app()->errorHandler->error.
     * This property defaults to null, meaning CErrorHandler will handle the error display.
     */
    public $errorAction;

    protected $_error;
    protected $_exception;

    /**
     * Handles the exception/error event.
     * This method is invoked by the application whenever it captures
     * an exception or PHP error.
     * @param Event $event the event containing the exception/error information
     */
    public function handle($event)
    {
        // set event as handled to prevent it from being handled by other event handlers
        $event->handled = true;

        if ($this->discardOutput) {
            $gzHandler = false;
            foreach (ob_list_handlers() as $h) {
                if (strpos($h, 'gzhandler') !== false)
                    $gzHandler = true;
            }
            // the following manual level counting is to deal with zlib.output_compression set to On
            // for an output buffer created by zlib.output_compression set to On ob_end_clean will fail
            for ($level = ob_get_level(); $level > 0; --$level) {
                if (!@ob_end_clean())
                    ob_clean();
            }
            // reset headers in case there was an ob_start("ob_gzhandler") before
            if ($gzHandler && !headers_sent() && ob_list_handlers() === array()) {
                if (function_exists('header_remove')) // php >= 5.3
                {
                    header_remove('Vary');
                    header_remove('Content-Encoding');
                } else {
                    header('Vary:');
                    header('Content-Encoding:');
                }
            }
        }

        if ($event instanceof ExceptionEvent)
            $this->handleException($event->exception);
        else // ErrorEvent
            $this->handleError($event);
    }

    /**
     * Returns the details about the error that is currently being handled.
     * The error is returned in terms of an array, with the following information:
     * <ul>
     * <li>code - the HTTP status code (e.g. 403, 500)</li>
     * <li>type - the error type (e.g. 'CHttpException', 'PHP Error')</li>
     * <li>message - the error message</li>
     * <li>file - the name of the PHP script file where the error occurs</li>
     * <li>line - the line number of the code where the error occurs</li>
     * <li>trace - the call stack of the error</li>
     * <li>source - the context source code where the error occurs</li>
     * </ul>
     * @return array the error details. Null if there is no error.
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the instance of the exception that is currently being handled.
     * @return Exception|null exception instance. Null if there is no exception.
     */
    public function getException()
    {
        return $this->_exception;
    }

    /**
     * Handles the exception.
     * @param Exception $exception the exception captured
     */
    protected function handleException($exception)
    {
        Mindy::app()->middleware->processException($exception);

        $app = Mindy::app();
        if ($app instanceof Application) {
            if (($trace = $this->getExactTrace($exception)) === null) {
                $fileName = $exception->getFile();
                $errorLine = $exception->getLine();
            } else {
                $fileName = $trace['file'];
                $errorLine = $trace['line'];
            }

            $trace = $exception->getTrace();

            foreach ($trace as $i => $t) {
                if (!isset($t['file']))
                    $trace[$i]['file'] = 'unknown';

                if (!isset($t['line']))
                    $trace[$i]['line'] = 0;

                if (!isset($t['function']))
                    $trace[$i]['function'] = 'unknown';

                unset($trace[$i]['object']);
            }

            $this->_exception = $exception;
            $code = ($exception instanceof HttpException) ? $exception->statusCode : 500;
            $this->_error = $data = array(
                'code' => $code,
                'type' => get_class($exception),
                'errorCode' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $fileName,
                'line' => $errorLine,
                'trace' => $exception->getTraceAsString(),
                'traces' => $trace,
            );

            if (!headers_sent()) {
                header("HTTP/1.0 {$code} " . $this->getHttpHeader($code, get_class($exception)));
            }

            $this->renderException();
        } else
            $app->displayException($exception);
    }

    /**
     * Handles the PHP error.
     * @param ErrorEvent $event the PHP error event
     */
    protected function handleError($event)
    {
        $msg = "Error: {$event->message}\nFile: {$event->file}\nLine: {$event->line}";
        Mindy::app()->middleware->processException(new Exception($msg));

        $trace = debug_backtrace();
        // skip the first 3 stacks as they do not tell the error position
        if (count($trace) > 3)
            $trace = array_slice($trace, 3);
        $traceString = '';
        foreach ($trace as $i => $t) {
            if (!isset($t['file']))
                $trace[$i]['file'] = 'unknown';

            if (!isset($t['line']))
                $trace[$i]['line'] = 0;

            if (!isset($t['function']))
                $trace[$i]['function'] = 'unknown';

            $traceString .= "#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";
            if (isset($t['object']) && is_object($t['object']))
                $traceString .= get_class($t['object']) . '->';
            $traceString .= "{$trace[$i]['function']}()\n";

            unset($trace[$i]['object']);
        }

        $app = Mindy::app();
        if ($app instanceof Application) {
            switch ($event->code) {
                case E_WARNING:
                    $type = 'PHP warning';
                    break;
                case E_NOTICE:
                    $type = 'PHP notice';
                    break;
                case E_USER_ERROR:
                    $type = 'User error';
                    break;
                case E_USER_WARNING:
                    $type = 'User warning';
                    break;
                case E_USER_NOTICE:
                    $type = 'User notice';
                    break;
                case E_RECOVERABLE_ERROR:
                    $type = 'Recoverable error';
                    break;
                default:
                    $type = 'PHP error';
            }
            $this->_exception = null;
            $this->_error = array(
                'code' => 500,
                'type' => $type,
                'message' => $event->message,
                'file' => $event->file,
                'line' => $event->line,
                'trace' => $traceString,
                'traces' => $trace,
            );
            if (!headers_sent()) {
                header("HTTP/1.0 500 Internal Server Error");
            }
            $this->renderError();
        } else {
            $app->displayError($event->code, $event->message, $event->file, $event->line);
        }
    }

    /**
     * whether the current request is an AJAX (XMLHttpRequest) request.
     * @return boolean whether the current request is an AJAX request.
     */
    protected function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Returns the exact trace where the problem occurs.
     * @param Exception $exception the uncaught exception
     * @return array the exact trace where the problem occurs
     */
    protected function getExactTrace($exception)
    {
        $traces = $exception->getTrace();

        foreach ($traces as $trace) {
            // property access exception
            if (isset($trace['function']) && ($trace['function'] === '__get' || $trace['function'] === '__set'))
                return $trace;
        }
        return null;
    }

    /**
     * Renders the view.
     * @param string $view the view name (file name without extension).
     * See {@link getViewFile} for how a view file is located given its name.
     * @param array $data data to be passed to the view
     */
    protected function render($view, $data)
    {
        // additional information to be passed to view
        $data['version'] = $this->getVersionInfo();
        $data['time'] = time();
        $data['admin'] = $this->adminInfo;

        if (!isset($data['code'])) {
            $template = $this->getViewFile($view, '');
        } else {
            $template = $this->getViewFile($view, $data['code']);
        }

        if ($template === null) {
            $template = $this->getViewFile($view, '');
        }

        if ($template === null) {
            $ext = Mindy::app()->viewRenderer->fileExtension;
            $views = implode(' ', [$view . $data['code'] . $ext, $view . $ext]);
            throw new Exception("Template not found: $views. Search paths:\n" . implode("\n", Mindy::app()->finder->getPaths()));
        }

        echo Mindy::app()->viewRenderer->render($template, [
            'data' => $data,
            'this' => $this
        ]);
    }

    /**
     * Renders the exception information.
     * This method will display information from current {@link error} value.
     */
    protected function renderException()
    {
        $exception = $this->getException();
        if ($exception instanceof Exception || !YII_DEBUG) {
            $this->renderError();
        } else {
            if ($this->isAjaxRequest()) {
                Mindy::app()->displayException($exception);
            } else {
                $this->render('exception', $this->getError());
            }
        }
    }

    /**
     * Renders the current error information.
     * This method will display information from current {@link error} value.
     */
    protected function renderError()
    {
        $data = $this->getError();
        if (YII_DEBUG)
            $this->render('exception', $data);
        else
            $this->render('error', $data);
    }

    /**
     * Looks for the view under the specified directory.
     * @param string $viewPath the directory containing the views
     * @param string $view view name (either 'exception' or 'error')
     * @param integer $code HTTP status code
     * @param string $srcLanguage the language that the view file is in
     * @return string view file path
     */
    protected function getViewFileInternal($viewPath, $view, $code, $srcLanguage = null)
    {
        $app = Mindy::app();
        if ($view === 'error') {
            $viewFile = $app->findLocalizedFile($viewPath . DIRECTORY_SEPARATOR . "error{$code}.php", $srcLanguage);
            if (!is_file($viewFile))
                $viewFile = $app->findLocalizedFile($viewPath . DIRECTORY_SEPARATOR . 'error.php', $srcLanguage);
        } else
            $viewFile = $viewPath . DIRECTORY_SEPARATOR . "exception.php";
        return $viewFile;
    }

    /**
     * Returns server version information.
     * If the application is in production mode, empty string is returned.
     * @return string server version information. Empty if in production mode.
     */
    protected function getVersionInfo()
    {
        if (YII_DEBUG) {
            $version = '<a href="http://www.mindy-cms.com/">Mindy Framework</a>/' . Mindy::getVersion();
            if (isset($_SERVER['SERVER_SOFTWARE']))
                $version = $_SERVER['SERVER_SOFTWARE'] . ' ' . $version;
        } else
            $version = '';
        return $version;
    }

    /**
     * Converts arguments array to its string representation
     *
     * @param array $args arguments array to be converted
     * @return string string representation of the arguments array
     */
    protected function argumentsToString($args)
    {
        $count = 0;

        $isAssoc = $args !== array_values($args);

        foreach ($args as $key => $value) {
            $count++;
            if ($count >= 5) {
                if ($count > 5)
                    unset($args[$key]);
                else
                    $args[$key] = '...';
                continue;
            }

            if (is_object($value))
                $args[$key] = get_class($value);
            elseif (is_bool($value))
                $args[$key] = $value ? 'true' : 'false';
            elseif (is_string($value)) {
                if (strlen($value) > 64)
                    $args[$key] = '"' . substr($value, 0, 64) . '..."';
                else
                    $args[$key] = '"' . $value . '"';
            } elseif (is_array($value))
                $args[$key] = 'array(' . $this->argumentsToString($value) . ')';
            elseif ($value === null)
                $args[$key] = 'null';
            elseif (is_resource($value))
                $args[$key] = 'resource';

            if (is_string($key)) {
                $args[$key] = '"' . $key . '" => ' . $args[$key];
            } elseif ($isAssoc) {
                $args[$key] = $key . ' => ' . $args[$key];
            }
        }
        $out = implode(", ", $args);

        return $out;
    }

    /**
     * Returns a value indicating whether the call stack is from application code.
     * @param array $trace the trace data
     * @return boolean whether the call stack is from application code.
     */
    protected function isCoreCode($trace)
    {
        if (isset($trace['file'])) {
            $systemPath = realpath(dirname(__FILE__) . '/..');
            return $trace['file'] === 'unknown' || strpos(realpath($trace['file']), $systemPath . DIRECTORY_SEPARATOR) === 0;
        }
        return false;
    }

    /**
     * Renders the source code around the error line.
     * @param string $file source file path
     * @param integer $errorLine the error line number
     * @param integer $maxLines maximum number of lines to display
     * @return string the rendering result
     */
    protected function renderSourceCode($file, $errorLine, $maxLines)
    {
        $errorLine--; // adjust line number to 0-based from 1-based
        if ($errorLine < 0 || ($lines = @file($file)) === false || ($lineCount = count($lines)) <= $errorLine)
            return '';

        $halfLines = (int)($maxLines / 2);
        $beginLine = $errorLine - $halfLines > 0 ? $errorLine - $halfLines : 0;
        $endLine = $errorLine + $halfLines < $lineCount ? $errorLine + $halfLines : $lineCount - 1;

        $output = '';
        for ($i = $beginLine; $i <= $endLine; ++$i) {
            $code = sprintf("%s", htmlentities(str_replace("\t", '    ', $lines[$i]), ENT_QUOTES, Mindy::app()->charset));
            $output .= $code;
        }
        return strtr('<pre class="brush: php; highlight: {errorLine}; first-line: {beginLine}; toolbar: false;">{content}</pre>', [
            '{beginLine}' => $beginLine,
            '{errorLine}' => $errorLine,
            '{content}' => $output
        ]);
    }

    /**
     * Return correct message for each known http error code
     * @param integer $httpCode error code to map
     * @param string $replacement replacement error string that is returned if code is unknown
     * @return string the textual representation of the given error code or the replacement string if the error code is unknown
     */
    protected function getHttpHeader($httpCode, $replacement = '')
    {
        $httpCodes = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            118 => 'Connection timed out',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            210 => 'Content Different',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            310 => 'Too many Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I’m a teapot',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway ou Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            509 => 'Bandwidth Limit Exceeded',
        ];
        return isset($httpCodes[$httpCode]) ? $httpCodes[$httpCode] : $replacement;
    }

    /**
     * @deprecated
     * @param $args
     * @return string
     */
    public function argsToString($args)
    {
        return $this->argumentsToString($args);
    }

    /**
     * @deprecated
     * @param $trace
     * @return bool
     */
    public function isCore($trace)
    {
        return $this->isCoreCode($trace);
    }

    /**
     * @deprecated
     * @param $file
     * @param $errorLine
     * @param $maxLines
     * @return string
     */
    public function renderSource($file, $errorLine, $maxLines)
    {
        return $this->renderSourceCode($file, $errorLine, $maxLines);
    }

    protected function getViewFile($view, $code)
    {
        $ext = Mindy::app()->viewRenderer->fileExtension;
        return Mindy::app()->finder->find('core/' . $view . $code . $ext);
    }
}