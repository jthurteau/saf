<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Ui Utility for Saf\Debug
 */

namespace Saf\Util\Debug;

use Saf\Debug;
use Saf\Hash;
use Saf\Util\Layout;
use Saf\Util\Debug\Mute;

class Ui 
{
    public const NON_NOTIFY_LEVELS = [
        'STATUS',
        'OTHER',
    ];
    
    public const string ICON_PROFILE_INFO = 'tachometer-alt';
    public const string ICON_DEBUG_INFO = 'bug';
    public const string ICON_EXPAND_TEXT = 'show stack trace';

    protected static $buffer = '';
    protected static $notifyConsole = false;
    protected static $buffered = true;
    protected static $maxBufferSize = 1000000;
    protected static $bufferOverflow = false;
    protected static $printedDebugExit = false;
    protected static $printedDebugEntry = false;
    protected static $printedDebugShutdown = false;
    protected static bool $foundationMode = true;

    public static function noop()
    {
        
    }

    public static function out(string $level, string $message, ?array $trace = null): void
    {
        $expandIcon = self::expandIcon();
        $htmlLevel = self::htmlLevel($level);
        $htmlTrace = self::htmlTrace($trace);
        $message = htmlentities($message);
        $output = "<div class=\"debug{$htmlLevel}\"><p>{$message}{$expandIcon}</p>{$htmlTrace}</div>\n";
        $notify = in_array(strtoupper($level), self::NON_NOTIFY_LEVELS);
        self::goOut($output, $notify);
    }

    public static function outData(string $level, mixed $message, ?array $trace = null): void
    {
        $expandIcon = self::expandIcon();
        $htmlLevel = self::htmlLevel($level);
        $htmlTrace = self::htmlTrace($trace);
        $output = "<div class=\"debug{$htmlLevel}\"><p>Data:{$expandIcon}</p>{$htmlTrace}<pre class=\"data\">";
        $output .= htmlspecialchars(Debug::introspectData($message));
        $output .= "</pre></div>\n";
        $notify = in_array(strtoupper($level), self::NON_NOTIFY_LEVELS);
        self::goOut($output, $notify);
    }

    public static function outRaw(string $message, ?bool $preformated = true): void
    {
        $wrappedOutput = 
            ($preformated ? '<pre class="message">' : '') 
            . $message 
            . ($preformated ? '</pre>' : '');
        self::goOut($wrappedOutput, false);
    }

    public static function outRawData(mixed $message,  ?bool $preformated = true): void
    {
        $output = Debug::introspectData($message);
        $wrappedOutput = 
            ($preformated ? '<pre class="data">' : '')
            . htmlspecialchars($output)
            . ($preformated ? '</pre>' : '');
        self::goOut($wrappedOutput, false);
    }

    protected static function htmlLevel($level)
    {
        return htmlentities(ucfirst(strtolower($level)));
    }

    protected static function goOut(string $output, ?bool $notifyConsole = true): void
    {
        self::$notifyConsole = self::$notifyConsole || $notifyConsole;
        if (self::$buffered) {
            if (strlen(self::$buffer) + strlen($output) > self::$maxBufferSize) {
                self::$bufferOverflow = true; //#TODO #2.0.0 treat overflow as an int for count?
            } else {
                self::$buffer .= $output;
            }
        } else {
            print($output);
        }
    }

    public static function expandIcon(): string
    {
        return 
            '<span class="debugExpand"> '
            . Layout::getIcon(self::ICON_DEBUG_INFO)
            . '<span class="icon-placeholder">'
            . self::ICON_EXPAND_TEXT.'</span></span>';
    }

    public static function htmlTrace($trace)
    {
        if (!$trace) {
            return '';
        }
        $traceLines = [];
        foreach ($trace as $lineIndex => $line) {
            $traceLines[] = "#{$lineIndex} "
                . (key_exists('file', $line) ? $line['file'] : '')
                . (key_exists('line', $line) ? "({$line['line']}):" : '[internal function]:')
                . ($lineIndex && key_exists('class', $line) ? " {$line['class']}" : '')
                . ($lineIndex && key_exists('type', $line) ? "{$line['type']}" : '')
                . ($lineIndex && key_exists('function', $line) ? " {$line['function']}" : '')
                . ($lineIndex && key_exists('args', $line) ? self::paramSummary($line['args']) : '');
            //{$line['line']} {$line['class']} {$line['function']} {$line['type']} {$line['args']}";
        }
        $style = ' style="display:none;"';
        return 
            "<pre class=\"debugTrace\"{$style}>" 
            . htmlentities(implode("\r\n", $traceLines))
            . '</pre>';
    }

    public static function paramSummary($args)
    {//#TODO #2.0.0, pass to Analysis?
        return '(args...)';
    }

    public static function startBuffer()
    {
        self::$buffered = true;
    }

    public static function endBuffer()
    {
        self::$buffered = false;
    }

    public static function getBuffer()
    {
        return self::$buffer;
    }

    public static function cleanBuffer()
    {
        //print_r([__FILE__,__LINE__,'clean']);
        if (false) {
        //if (self::$_verbose && Layout::formatIsHtml()) {
            print('<!-- debug buffer cleared -->');
        }
        self::$buffer = '';
        self::$bufferOverflow = false;
    }

    public static function flushBuffer($force = false)
    {
        $return = self::getBuffer();
        if ($force || Debug::isVerbose()) { //#TODO Debug::
            $warnings = self::$bufferOverflow ? ' #OVERFLOW' : '';
            print('<!-- debug buffer contents ' . strlen($return) . "{$warnings} -->");
            print($return);
        }
        self::cleanBuffer();
        return $return;
    }

    public static function getRequestString() #TODO modernize this with PSR7, parametarized redaction list
    {
        ob_start();
        $sanitizedRequest = $_REQUEST;
        if (array_key_exists('pwd', $sanitizedRequest)) {
            $sanitizedRequest['pwd'] = '***redacted***';
        }
        if (array_key_exists('password', $sanitizedRequest)) {
            $sanitizedRequest['password'] = '***redacted***';
        }
        print('<ul class="debugList">');
        foreach($sanitizedRequest as $key=>$item) {
            if (is_array($item)) {
                $item = Hash::toString($item);
            }
            print('<li class="noBullet">[' . htmlentities($key) . '] : ' . '<span class="literal">' . htmlentities($item) . '</span></li>');
        }
        print('</ul>');
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public static function outputRequest()
    {
        $output = '<div class="debugStatus">Request:';
        $output .= self::getRequestString();
        $output .= '</div>';
        self::goOut($output, false);
    }

    public static function printDebugExit($force = false)
    {
        if (!self::$printedDebugExit || $force) {
            if (false) {
            //if (Layout::formatIsHtml()) {
                if (!Debug::isForced()) {
                    print("\n<p class=\"debugOther\"><a href=\"?nodebug=true\">Disable debugging for this session.</a></p>\n");
                } else {
                    print("\n<p class=\"debugOther\">Debugging is forced to on.</p>\n");
                }
            }//#TODO #2.0.0 figure out what to do for other formats...
            self::$printedDebugExit = true;
        }
    }

    public static function printDebugEntry($force = false)
    {
        if (Debug::isAvailable() && !Debug::isVerbose()) {
            if (!self::$printedDebugEntry || $force) {
                $mode = Debug::getMode();
                if (false) {
                //if (Layout::formatIsHtml()) {
                    print("\n<p class=\"debugEntry\"><a href=\"?debug=true\">Enable debugging for this session. Debug mode: {$mode}</a></p>\n");
                } //#TODO #2.0.0 figure out what to do for other formats...
            }
            self::$printedDebugEntry = true;
        }
    }

    public static function printDebugShutdown()
    {
        if (false) {
        //if (!self::$printedDebugShutdown && Layout::formatIsHtml()) {
            $loadTime = microtime(true) - APPLICATION_START_TIME;
            if (Debug::isVerbose()) {
                if (Mute::active()) {
                    foreach (Mute::list() as $trace) {
                        $icon = self::expandIcon();
                        print("\n<div class=\"debugStatus\"><pre>Data:{$icon}<br/>\n");
                        print(htmlentities($trace));
                        print('Unclosed Mute');
                        print("\n</pre></div>\n");
                    }
                }
            }
            self::$buffered = false;
            if (Debug::isVerbose() && strlen(self::$buffer) > 0) {
                print('<div class="debugStatus">Unsent Debug Buffer:<br/><pre>');
                print(self::$buffer);
                print('</pre></div>');
            }
            self::out("This page took {$loadTime} seconds to load.", 'STATUS');
            self::$printedDebugShutdown = true;
        }
    }

    public static function printDebugAnchor()
    {
        print('<span id="debugTop"></span>');
    }
    
    public static function printDebugReveal()
    {
        //#CLEAN print_r([__FILE__,__LINE__,'hi9',self::$foundationMode]); die;
        if (self::$foundationMode) {
        //if (Layout::isReady()) {
            $icon = self::getIcon();
            $accessible = ' class="accessibleHidden"';
        } else {
            $icon = '';
            $accessible = '';
        }
        print(
            '<div id="showDebug"><a href="#debug">'
            . "{$icon}<span{$accessible}>Show Debug Information</span></a></div>"
        );
    }

    public static function printProfileReveal()
    {
        if (self::$foundationMode) {
        //if (Layout::isReady()) {
            $icon = self::getProfileIcon();
            $accessible = ' class="accessibleHidden"';
        } else {
            $icon = '';
            $accessible = '';
        }
        print(
            '<div id="showProfile"><a href="#profile">'
            . "{$icon}<span{$accessible}>Show Profiling Information</span></a></div>"
        );
    }

    public static function outDebugBlockStart($level = 'ERROR')
    {
        //#TODO #2.0.0 sanitize $level
        self::goOut("<div class=\"debug{$level}\">");
    }

    public static function outDebugBlockEnd()
    {
        self::goOut('</div>');
    }

    public static function getIcon(): string
    {
        return Layout::getIcon(self::ICON_DEBUG_INFO);
    }

    public static function getProfileIcon(): string
    {
        return Layout::getIcon(self::ICON_PROFILE_INFO);
    }

}