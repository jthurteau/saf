<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for debugging
 */

namespace Saf;

use Saf\Cast;
use Saf\Session;
use Saf\Util\Debug\Handler;
use Saf\Util\Debug\Analysis;
use Saf\Util\Debug\Ui;
//#TODO Saf\Util\Debug\Mute?
//use Saf\Util\Debug\Trace;
//#TODO patch into Saf\Meditation;
use Saf\Meditation\Configuration as ConfigurationMeditation;

require_once(__DIR__.'/Session.php');
require_once(__DIR__.'/Util/Debug/Handler.php');
require_once(__DIR__.'/Util/Debug/Analysis.php');
require_once(__DIR__.'/Util/Debug/Ui.php');
require_once(__DIR__.'/Meditation/Configuration.php');

class Debug
{
    /**
     * mode flag that forces debugging to be enabled and verbose
     */
    public const string MODE_FORCE = 'force';

    /**
     * mode flag that forces debugging to be disabled and silent
     */
    public const string MODE_DISABLE = 'disable';

    /**
     * mode flag that enables debugging, defaults to verbose (toggle-able) 
     */
    public const string MODE_ON = 'on';

    /**
     * mode flag that temporarily turns off debugging features
     */
    public const string MODE_OFF = 'off';

    /**
     * mode flag that leaves debugging enabled, but disables non-profiling output
     */
    public const string MODE_SILENT = 'silent';

    /**
     * debugging level that indicates an error or exception
     */
    public const string LEVEL_ERROR = 'ERROR';

    /**
     * debugging level that indicates a non-error to be profiled
     */
    public const string LEVEL_PROFILE = 'PROFILE';
    
    /**
     * debugging level that indicates a non-error (not profiled)
     */
    public const string LEVEL_STATUS = 'STATUS';
    
    /**
     * error mode that enables internal error/exception/shutdown handlers
     */
    public const string ERROR_MODE_INTERNAL = 'internal';
    
    /**
     * error mode that enables external plug-in driven handling
     */
    public const string ERROR_MODE_PLUGIN = 'plugin';
    
    /**
     * error mode that disables all internal handling features
     */
    public const string ERROR_MODE_EXTERNAL = 'default';

    /**
     * simplest trace output level that excludes args
     */
    public const string TRACE_BARE = 'bare';

    /**
     * trace output level that mimics Exception::traceAsString
     */
    public const string TRACE_SHALLOW = 'shallow';

    /**
     * trace output level with more detail than shallow;
     */
    public const string TRACE_DIGEST = 'digest';

    /**
     * trace output level that scans the depth of structures and generates prints
     */
    public const string TRACE_DEEP = 'deep';

    /**
     * trace output level, caches the full details of printed structures
     */
    public const string TRACE_VAULTED = 'vault';

    /**
     * standard EOL
     */
    public const string EOL = "\n";
   
    /**
     * key in $_Session to store debug mode state
     */
    public const string SESSION_SWITCH = 'debug';

    /**
     * key in $_Session to store debug mode state
     */
    public const string SESSION_OFF_SWITCH = 'no'.self::SESSION_SWITCH;

    /**
     * key in $_Session to store debug mode state
     */
    public const string SESSION_MUTE_SWITCH = 'silent'.self::SESSION_SWITCH;

    /**
     * output format: plain text
     */
    public const string FORMAT_TEXT = 'text';
    
    /**
     * output format: HTML
     */
    public const string FORMAT_HTML = 'html';
    
    /**
     * output format: JSON
     */
    public const string FORMAT_JSON = 'json';

    /**
     * current debugging mode
     */

    protected static ?string $mode = null;

    /**
     * flag indicating debugging settings have synced with session data
     */
    protected static $sessionReady = false;

    /**
     * trace formatting rules to use on halt
     */
    protected static $haltRenderLevel = self::TRACE_DIGEST;

    /**
     * trace formatting rules to use on halt
     */
    protected static $haltRenderFormat = self::FORMAT_TEXT;

    /**
     * Initilizes debugging
     * @param string $mode indicates which MODE_ constant to use
     * @param mixed $errorHandler indicates an ERROR_MODE_ contant, or handling plug-in object
     */
    public static function init(
        ?string $mode = self::MODE_SILENT, 
        null|string|array|Object $errorHandler = self::ERROR_MODE_EXTERNAL
    ): ?string {
        //print_r([__FILE__,__LINE__, $mode, self::caller(true)]); die;
        Handler::init();
        self::sessionCheck();
        if ($errorHandler == self::ERROR_MODE_INTERNAL) {
            Handler::takeover();
        } elseif (!is_null($errorHandler) && $errorHandler !== self::ERROR_MODE_EXTERNAL) {
            Handler::install($errorHandler);
        }
        self::switchMode($mode);
        return self::$mode;
    }

    /**
     * returns the current mode
     * @return string MODE_ constant
     */
    public static function getMode() : ?string
    {
        return self::$mode;
    }

    /**
     * @return bool force enabled, verbose
     */
    public static function isForced(): bool
    {
        return self::MODE_FORCE == self::$mode;
    }

    /**
     * @return bool is not force disabled
     */
    public static function isAvailable(): bool
    {
        return self::MODE_DISABLE != self::$mode;
    }

    /**
     * @return bool is enabled (may be verbose or silenced)
     */
    public static function isEnabled(): bool
    {
        return
            !is_null(self::$mode)
            && self::$mode != self::MODE_OFF
            && self::$mode != self::MODE_DISABLE;
    }

    /**
     * @param string $mode MODE_ constant to switch to
     * @return string resulting MODE_
     */
    public static function switchMode(?string $mode = self::MODE_OFF): void
    {
        //print_r([__FILE__,__LINE__,$mode, self::$mode, gettype(self::$mode),self::caller(true)]); die;
        is_null($mode) && ($mode = self::MODE_OFF);
        if (self::isForced() || !self::isAvailable()) {
            return;
        }
        $previousMode = self::$mode;
        self::$mode = strtolower(trim($mode));
        switch (self::$mode) {
            case self::MODE_DISABLE:
                self::off();
                break;
            case self::MODE_OFF:
            case self::MODE_SILENT:
            case self::MODE_ON:
                self::auto();
                break;
            case self::MODE_FORCE:
                self::on();
                break;
            default:
                $badMode = self::$mode;
                self::$mode = $previousMode;
                throw new ConfigurationMeditation("Unknown Debug Mode: {$badMode}");
        }
    }

    public static function registerHandlers($which = null): void
    {
        Handler::takeover();
    }

    /**
     * @return bool session detected (session is ready and available)
     */
    public static function sessionCheck(): bool
    {
        self::$sessionReady = self::$sessionReady || Session::ready();
        return self::$sessionReady;
    }

    /**
     * writes debug mode to session if the session was not available during init 
     * and reapplies mode settings
     * ! Frameworks and Apps should call this if session is initialized after debug.
     */
    public static function sessionReadyListner(): void
    {
        if (!self::$sessionReady && Session::ready()) {
            self::sessionCheck();
            self::switchMode(self::$mode ?: self::MODE_DISABLE);
        }
    }

    /**
     * updates session data (when available) with current mode
     */
    public static function updateSession(): void
    {
        if (self::$sessionReady) {
            Session::set(self::SESSION_SWITCH, Cast::dmvl(self::$mode, self::MODE_ON, self::MODE_OFF));
        }
    }

    /**
     * turns off "debugging" features (internal and PHP native)
     */
    public static function off(?bool $native = true): void
    {
        Handler::off();
        $native && self::updateNativeState();
    }

    /**
     * mutes "debugging" features (internal and PHP native)
     */
    public static function hush(?bool $native = true): void
    {
        Handler::hush();
        $native && self::updateNativeState();
    }

    /**
     * turns on "debugging" features and makes output verbose (internal and PHP native)
     */
    public static function on(?bool $native = true): void
    {
        Handler::on();
        $native && self::updateNativeState();
    }

    /**
     * unmutes "debugging" features (internal or PHP native)
     */
    public static function broadcast(?bool $native = true): void
    {
        Handler::broadcast();
        $native && self::updateNativeState();
    }

    /**
     * updates the native PHP error display/reporting
     */
    protected static function updateNativeState(): void
    {
        //self::outData(['switching display_errors setting to:', Handler::getDisplayMode()]);
        ini_set('display_errors', Handler::getDisplayMode());
        error_reporting(Handler::getErrorLevel());
    }

    /**
     * updates Debugging behavior
     */
    public static function auto()
    {//#TODO allow binding to PSR7 on init?
        //print_r([__FILE__,__LINE__, self::$mode, gettype(self::$mode),self::caller(true)]); die;
        $oldMode = self::$mode;
        if (key_exists(self::SESSION_OFF_SWITCH, $_GET)) {
            self::$mode = self::MODE_OFF;
        } elseif (key_exists(self::SESSION_SWITCH, $_GET)) {
            self::$mode = self::MODE_ON;
        } elseif (key_exists(self::SESSION_MUTE_SWITCH, $_GET)) {
            self::$mode = self::MODE_SILENT;
        } elseif (self::$sessionReady && Session::has(self::SESSION_SWITCH)) {
            self::$mode = Cast::mvl(Session::get(self::SESSION_SWITCH), self::MODE_ON, self::MODE_OFF);
        }
        switch (self::$mode) {
            case self::MODE_OFF:
                self::off();
                break;
            case self::MODE_SILENT:
                self::hush();
                break;
            case self::MODE_ON:
                self::on();
                break;
            default:
                $badMode = self::$mode;
                self::$mode = $oldMode;
                throw new ConfigurationMeditation("Unknown Debug Mode: {$badMode}");
        }
        self::$sessionReady && self::updateSession();
    }

    /**
     * @return bool is currently verbose
     */
    public static function isVerbose()
    {
        return 
            self::MODE_ON == self::$mode 
            || self::MODE_FORCE == self::$mode;
    }

    /**
     * @return bool defaults to on/enabled
     */
    public static function isDefault()
    {
        return 
            self::MODE_ON == self::$mode
            || self::MODE_SILENT == self::$mode
            || self::MODE_FORCE == self::$mode;
    }


    public static function setErrorLevel($level): void
    {
        Handler::enabledErrorLevel($level);
    }

    public static function out(string $message, ?string $level = self::LEVEL_ERROR): void 
    {
        Handler::out($level, $message, self::getTrace());
    }

    public static function outRaw(string $message, ?bool $preformat = false): void
    {
        Handler::outRaw($message, self::getTrace());
    }

    public static function outData(mixed $message, ?string $level = self::LEVEL_ERROR): void
    {
        Handler::outData($level, $message, self::getTrace());
    }

    public static function outRawData(mixed $message, ?bool $preformat = false): void
    {
        Handler::outRawData($message, $preformat);
    }

    public static function introspectData(mixed $message): string //#TODO consolidate with Hash:introspectData?
    {
        return Analysis::data($message);
    }

    public static function audit(array $point): void
    {
        Handler::audit($point);
    }

    public static function stringR(mixed $data = null): string
    {
        $args = func_get_args();
        return count($args) > 1 ? ('...[' . Analysis::data($args) . ']') : Analysis::data($data);
    }

    /**
     * die with print_r style output of the passed data (accepts multiple params),
     * only if debug is enabled
     * @param mixed|null $data to output
     * @return void
     */
    public static function dieR(mixed $data = null): void
    {
        if (self::isEnabled()) {
            $args = func_get_args();
            count($args) > 1 ? die(Analysis::stringR($args)) : die(Analysis::stringR($data));
        }
    }

    #TODO delegate trace/caller features to \Saf\Util\Debug\Trace
    /**
     * generates a stack trace, if wrapped is set and >0 that many levels are trimmed
     * from the top of the stack.
     * By default (true/1) are removed the reflect the stack at the point of the caller.
     * @param bool $wrapped bool
     * @return array
     */
    public static function getTrace(bool|int $wrapped = true): array
    {
        is_bool($wrapped) && ($wrapped = $wrapped ? 1 : 0);
        try {
            throw new \Exception('debug');
        } catch (\Exception $e) {
            $trace = $e->getTrace();
            while($wrapped-- > 0) { #NOTE removes this(debug) object/method from the stack
                array_shift($trace);
            }
            return $trace;
        }
    }

    public static function getTraceString(bool|int $wrapped = true): string
    {
        is_bool($wrapped) && ($wrapped = $wrapped ? 1 : 0);
        try {
            throw new \Exception('debug');
        } catch (\Exception $e) {
            $trace = explode(PHP_EOL, $e->getTraceAsString());
            while(0 < $wrapped--) { #NOTE removes this(debug) object/method from the stack
                array_shift($trace);
            }
            foreach($trace as $number => $line) {
                $parts = explode(' ', $line, 2);
                $parts[0] = '#' . (string)((int)substr($parts[0],1) - 1);
                $trace[$number] = implode(' ', $parts);
            }
            return implode(PHP_EOL, $trace);
        }
    }

    protected static function currentTraceString(?string $behavior = self::TRACE_SHALLOW): string
    {
        $e = new \Exception('debug');
        $rendered = Analysis::renderTrace(array_slice($e->getTrace(),1), $behavior);
        return $rendered;
    }

    public static function caller(?bool $fullTrace = false): string
    {
        $trace = self::getTraceString(2);
        $subStart = strpos($trace, ' ');
        return $fullTrace ? $trace : substr($trace, $subStart, strpos($trace, PHP_EOL) - $subStart);
    }

    public static function here(?string $level = self::TRACE_BARE): string
    {
        return self::there(self::getTrace(2), $level);
    }

    public static function there(\Error|\Exception $e, string $level = self::TRACE_BARE): string
    {
        return Analysis::renderTrace($e->getTrace(), $level);
    }

    public static function setHaltLevel(string $level): void
    {
        self::$haltRenderLevel = $level;
    }

    public static function setHaltFormat(string $format): void
    {
        self::$haltRenderFormat = $format;
    }

    public static function halt(): void
    {
        if (self::isEnabled()) {
            $standardEol = self::EOL;
            $data = func_get_args();
            print("halting with trace:{$standardEol}");
            if ($data) {
                print ("[{$standardEol}");
                foreach($data as $item) {
                    $stringData =
                        is_string($item)
                        ? "  {$item}"
                        : (
                            is_object($item) && is_a($item, '\Throwable')
                            ? Analysis::renderThrowable($item, 2)
                            : Analysis::renderArg($item, self::TRACE_DEEP)
                        );
                    print("{$stringData}{$standardEol}");
                }
                print ("]{$standardEol}");
            }
            print(self::currentTraceString(self::$haltRenderLevel));
            die;
        }
    }


    public static function vent(): never
    {
        if(self::isEnabled()) {
            $vent = require(__DIR__ . '/kickstart/debug.vent.php');
            print 
                is_callable($vent) 
                ? $vent(self::getTrace(), func_get_args()) 
                : $vent;
            die;
        }
    }

    public static function dieSafe($message = ''): void
    {
        Handler::dieSafe($message);
    }

    /**
     * disable Handler shutdown and use dieSafe instead.
     */
    public static function registerDieSafe()
    {
        Handler::terminates(false);
        register_shutdown_function('Debug::dieSafe');
    }

}
