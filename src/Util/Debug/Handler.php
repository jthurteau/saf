<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Internal Error/Exception Handler Utility for Saf\Debug
 */

namespace Saf\Util\Debug;

use Saf\Debug;
use Saf\Kickstart;
use Saf\Util\Http\Status;
use Saf\Util\Debug\Ui;
use Saf\Util\Time;
use Saf\Audit;

use Psr\Http\Message\ServerRequestInterface;

class Handler 
{

    public const array FOOTPRINT_METHOD = ['file', 'line', 'function', 'class', 'type', 'args'];
    public const array FOOTPRINT_FN_CALLER = ['file', 'line', 'args', 'function'];
    public const array FOOTPRINT_FUNCTION = ['file', 'line', 'function', 'args'];
    public const array FOOTRPRINT_NATIVE_METHOD = ['function', 'class', 'type', 'args'];

    public const array ERROR_LEVELS = [
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => 'E_COMPILE_ERROR',
        128 => 'E_COMPILE_WARNING',
        256 => 'E_USER_ERROR',
        512 => 'E_USER_WARNING',
        1024 => 'E_USER_NOTICE',
        2048 => 'E_STRICT',
        4096 => 'E_RECOVERABLE_ERROR',
        8192 => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED'
    ];

    public const array CRITICAL_ERRORS = [
        1,     // E_ERROR
        4,     // E_PARSE
        16,    // E_CORE_ERROR
        64,    // E_COMPILE_ERROR
        256,   // E_USER_ERROR
        2048,  // E_STRICT
        4096,  // E_RECOVERABLE_ERROR
        8192,  // E_DEPRECATED
        16384, // E_USER_DEPRECATED
    ];

    public const array ERROR_LABELS = [
        'E_ERROR' => 'error',
        'E_WARNING' => 'warning',
        'E_PARSE' => 'error',
        'E_NOTICE' => 'notice',
        'E_CORE_ERROR' => 'error',
        'E_CORE_WARNING' => 'warning',
        'E_COMPILE_ERROR' => 'error',
        'E_COMPILE_WARNING' => 'notice',
        'E_USER_ERROR' => 'error',
        'E_USER_WARNING' => 'warning',
        'E_USER_NOTICE' => 'notice',
        'E_STRICT' => 'warning',
        'E_RECOVERABLE_ERROR' => 'error',
        'E_DEPRECATED' => 'warning',
        'E_USER_DEPRECATED' => 'warning'
    ];

    protected static bool $inControl = false;
    protected static bool $shutdownRegistered = false;
    protected static bool $shuttingDown = false;
    protected static bool $terminateOnShutdown = true;

    protected static $plugin = null;
    protected static $oldErrorHandler = null;
    protected static $oldExceptionHandler = null;

    /**
     * the 'display_errors' setting to use when enabled
     */
    protected static null|int|string $enabledDisplayMode = 1;

    /**
     * the 'display_errors' setting to use when disabled (detected from default setting at init)
     */
    protected static null|int|string $disabledDisplayMode = 0;

    /**
     * the 'display_errors' setting originally set upon init
     */
    protected static null|int|string $defaultDisplayMode = null;

    /**
     * the error_level to use for handling when enabled
     */
    protected static int $enabledErrorLevel = -1;

    /**
     * the error_level to use for handling when disabled
     */
    protected static int $disabledErrorLevel = -1;

    /**
     * the error_level setting originally detected upon init
     */
    protected static ?int $defaultErrorLevel = null;

    public static function init()
    {
        if (is_null(self::$defaultErrorLevel)) {
            self::$defaultErrorLevel = error_reporting();
            self::$defaultDisplayMode = ini_get('display_errors');
        }
    }

    public static function takeover()
    {
        if (!self::$inControl) {
            $self = self::class;
            self::$oldErrorHandler = set_error_handler("{$self}::handle");
            self::$oldExceptionHandler = set_exception_handler("{$self}::handleException");
            if (!self::$shutdownRegistered) {
                register_shutdown_function("{$self}::shutdown");
                self::$shutdownRegistered = true;
            }
            ini_set('display_errors', (string)self::$disabledDisplayMode);
            self::$inControl = true;
        }
    }

    public static function install($handler)
    {
        self::$plugin = $handler;
    }

    public static function relenquish()
    {
        if (
            !is_null(self::$oldErrorHandler)
            && self::$oldErrorHandler
        ) {
            set_error_handler(self::$oldErrorHandler);
            self::$oldErrorHandler = null;
        } else {
            restore_exception_handler();
        }
        if (
            !is_null(self::$oldExceptionHandler)
            && self::$oldExceptionHandler
        ) {
            set_error_handler(self::$oldExceptionHandler);
            self::$oldExceptionHandler = null;
        } else {
            restore_exception_handler();
        }
        self::$inControl = false;
    }

    public static function shutdown(): void
    {
        if (self::$inControl) {
            $error = error_get_last();
            if ($error && in_array($error['type'], self::CRITICAL_ERRORS)) {
                self::handle($error['type'], $error['message'], $error['file'], $error['line']);
            }
        }
    }

    public static function handleException(?\Throwable $e): void
    {
        if (!$e) {
            return;
        }
        $previous = $e->getPrevious();
        $class = get_class($e);
        self::outRaw('<div class="phpException">');
        self::handle(\E_RECOVERABLE_ERROR, "{$class}:{$e->getMessage()}", $e->getFile(), $e->getLine(),['e' => $e]);
        self::outRaw('<pre class="phpErrorTrace">');
        //self::outRawData($e->getTrace());
        self::outRaw($e->getTraceAsString());
        self::outRaw('</pre>');
        $previous && self::handleException($previous);
        self::outRaw('</div>');
    }

    public static function handle(
        int $errorNo, 
        string $errorString, 
        ?string $errorFile = null, 
        ?int $errorLine = null, 
        ?array $errorContext = []
    ): bool
    {
        $fatal = in_array($errorNo, self::CRITICAL_ERRORS);
        $description =
            key_exists($errorNo, self::ERROR_LEVELS)
            ? self::ERROR_LEVELS[$errorNo]
            : (is_numeric($errorNo) ? 'ERROR_NO_' . $errorNo : $errorNo);
        $at = $errorLine ? " on line {$errorLine}" : '';
        $in = $errorFile ? " in file {$errorFile}" . $at : $at;
        if ($fatal) {
            $caughtBy = self::$shuttingDown ? 'SHUTDOWN' : 'DEBUG';
            Status::set(Status::STATUS_500_ERROR);
            $e = 
                $errorContext && key_exists('e', $errorContext) 
                ? $errorContext['e']
                : new \Exception("{$description} {$in}: {$errorString}");
            // Kickstart::exceptionDisplay($e, $caughtBy, $errorString);
            // no longer exists....
            Debug::halt('debugger halting on fatal error handle', self::chain($e));
        } else {
            $show = self::$enabledErrorLevel === -1 || $errorNo & self::$enabledErrorLevel;
            if ($show && !Mute::active()) {
                $message = "<span class=\"phpErrorWhat\">{$description} - </span>"
                    . "<span class=\"phpErrorMessage\">{$errorString}</span>"
                    . "<span slass=\"phpErrorWhere\">{$in}</span> ";
                $trace = Debug::getTraceString();
                $level =
                    key_exists($description, self::ERROR_LABELS)
                        ? self::ERROR_LABELS[$description]
                        : 'error';
                $level = htmlentities(ucfirst(strtolower($level)));
                //$icon = $trace ? (' <span class="debugExpand"> ' . Layout::getIcon(self::LAYOUT_MORE_INFO_ICON) . '</span>') : '';
                $icon = '<span class="debugExpand">'.Ui::getIcon().'<span class="icon-placeholder"> '.Ui::ICON_EXPAND_TEXT.'</span></span>';
                $output = "{$message}{$icon}{$trace}\n";
                self::out(
                    Debug::LEVEL_PROFILE,
                    "<div class=\"debug{$level}\"><div class=\"phpError\">{$output}</div></div>"
                );
            }
        }
        return false;
    }

    public static function middlewareHook(object|array $request):void
    {
        $translatedTime = null;
        if(
            is_object($request) 
            && is_a($request, ServerRequestInterface::class)
        ) {
            $timeOffset = $request->getAttribute('debugOffset', null);
            if (!is_null($timeOffset)) {
                $translatedTime =
                    Time::isTimeStamp($timeOffset)
                    ? $timeOffset
                    : strtotime($timeOffset);
            } else {
                $get = $request->getQueryParams();
                $translatedTime = Time::parse($get['debugTime'] ?? '');
            }
        }
        !is_null($translatedTime) && Time::set($translatedTime);
    }

    public static function audit(array $point): void
    {
        #TODO make more robust
        if (
            array_keys($point) != self::FOOTPRINT_METHOD
            && array_keys($point) != self::FOOTPRINT_FUNCTION
            && array_keys($point) != self::FOOTPRINT_FN_CALLER
            && array_keys($point) != self::FOOTRPRINT_NATIVE_METHOD
        ) {
            Audit::add(
                'saf_debug',
                'unmatched trace signiture',
                [
                    'keys' => array_keys($point),
                ]
            );
        }
    }

    public static function terminates($set = null)
    {
        if (!is_null($set)) {
            self::$terminateOnShutdown = $set;
        }
        return self::$terminateOnShutdown;
    }

    public static function allowBroadcast()
    { //#TODO check plugin
        return self::$inControl;
    }

    public static function off()
    {

    }

    public static function on()
    {
        
    }

    public static function hush()
    {
        
    }

    public static function broadcast()
    {
        
    }

    public static function getDisplayMode(): int
    {
        return self::allowBroadcast() ? self::$enabledDisplayMode : self::$disabledDisplayMode;
    }

    public static function enabledDisplayMode(): int
    {
        return self::$enabledDisplayMode;
    }

    public static function disabledDisplayMode(): int
    {
        return self::$disabledDisplayMode;
    }

    public static function getErrorLevel(): ?int
    { // #TODO this needs work
        return self::allowBroadcast() ? self::$enabledErrorLevel : self::$disabledErrorLevel;
    }

    public static function enabledErrorLevel(?int $level = null): int
    {
        if (!is_null($level)) {
            self::$enabledErrorLevel = $level;
            if (Debug::isVerbose()) {
                error_reporting($level);
            }
        }
        return self::$enabledErrorLevel;
    }

    public static function disabledErrorLevel(): int
    {
        return self::$disabledErrorLevel;
    }

    public static function out(string $level, string $message, ?array $trace = null)
    {
        if (self::$plugin && method_exists(self::$plugin, 'out')){
            self::$plugin->out($level,$message, $trace);
        }
        Ui::out($level, $message, $trace);
    }

    public static function outRaw(string $message, ?bool $preformat = true)
    {
        if (self::$plugin && method_exists(self::$plugin, 'outRaw')){
            self::$plugin->outRaw($message, $preformat);
        }
        Ui::outRaw($message, $preformat);
    }

    public static function outData(string $level, mixed $message, ?array $trace = null)
    {
        if (self::$plugin && method_exists(self::$plugin, 'outData')){
            self::$plugin->outData($level,$message, $trace);
        }
        Ui::outData($level, $message, $trace);
    }

    public static function outRawData(mixed $message,  ?bool $preformat = true)
    {
        if (self::$plugin && method_exists(self::$plugin, 'outRawData')){
            self::$plugin->outRawData($message, $preformat);
        }
        Ui::outRawData($message, $preformat);
    }

    protected static function chain(?\Throwable $e): array
    {
        return $e ?[
            "{$e->getFile()}({$e->getLine()})", $e->getMessage(),  $e->getTraceAsString(), self::chain($e->getPrevious())
        ] : [];
    }

    public static function dieSafe($message = '')
    {
        if (self::isEnabled()) {
            if (false) {
            //if (self::$_notifyConsole && Layout::formatIsHtml()) {
                print('<script type="text/javascript">throw new Error("' . APPLICATION_DEBUG_NOTIFICATION . '");</script>');
            }
            Ui::printDebugShutdown();
            Ui::printDebugExit();
        } else if (self::isVerbose()) {
            Ui::printDebugEntry();
        }
        if (self::terminates()) {
            die($message);
        } elseif ($message) {
            print($message);
        }
    }

}