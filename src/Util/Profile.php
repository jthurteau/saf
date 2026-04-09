<?php 

/**
 * Utility class for profile debug output
 */

declare(strict_types=1);

namespace Saf\Util;

use Saf\Debug;

require_once(dirname(__DIR__) . '/Debug.php');

class Profile
{
    protected static ?int $microStartTime = null;
    protected static ?string $timeSource = null;
    protected static array $taggedSteps = [];

    /**
     * delegates profiling data to the debugger, if tagged saves it for later
     */
    public static function ping($data, null|string|array|\Traversable $tag = null): void
    {
        $notice = self::generateNotice();
        $tagNotice = 
            $tag
            ? (
                'ping for: ' . (is_array($tag) ? implode(', ', $tag) :$tag)
            ) : '';
        $message =  "{$notice}{$tagNotice} {" . Debug::introspectData($data) . '}';
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'ping', $data);
    }

    /**
     * tags one or more entry points
     */
    public static function in(string|array|\Traversable $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? implode(', ', $tag) : $tag;
        $message =  "{$notice}entry for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'in');
    }

    /**
     * tags one or more exit points
     */
    public static function out(string|array|\Traversable $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? implode(', ', $tag) : $tag;
        $message =  "{$notice}exit for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'out');
    }

    /**
     * store profile information for one or more tags with a $label and optional data
     */
    protected static function tag(string|array|\Traversable $tags, string $label, mixed $data = null): float
    {
        $momento = is_null($data) ? $label : [$label => $data];
        $time = self::getRunTime();
        $stringTime = (string)$time;
        foreach(is_array($tags) ? $tags : [$tags] as $currentTag) {
            key_exists($currentTag, self::$taggedSteps) || (self::$taggedSteps[$currentTag] = []);
            key_exists($stringTime, self::$taggedSteps[$currentTag])
                ? (self::$taggedSteps[$currentTag][$stringTime][] = $momento) 
                : (self::$taggedSteps[$currentTag][$stringTime] = [$momento]);
        }
        return $time;
    }

    /**
     * returns the start time for the current transaction, 
     * !starts the clock if not already started
     */
    public static function getStartTime(): float
    {
        return self::init() ?? self::$microStartTime;
    }

    /**
     * returns the total run time since the current transaction started,
     * !starts the clock if not already started
     */
    public static function getRunTime(): float
    {
        return microtime(true) - self::getStartTime();
    }

    /**
     * return a list of matching tagged steps, or all.
     * returns the tags re-organized by start time unless $chronological is false.
     */
    public static function getTags(null|string|array|\Traversable $tags = null, ?bool $chronological = true): array
    {
        $selected = 
            is_null($tags) 
            ? self::$taggedSteps 
            : (function($t) {
                    $selected = [];
                    foreach(is_string($t) ? [$t] : $t as $v) {
                        key_exists($v, self::$taggedSteps) && ($selected[] = $v);
                    }
                    return $selected;
                }
            )($tags);
        return $chronological ? self::flatten($selected) : $selected;
            
    }

    /**
     * reorders a tag indexed list of profile points into a chronological one
     */
    protected static function flatten(array $tags): array
    {
        $chron = [];
        foreach ($tags as $tag => $times) {
            foreach ($times as $time => $points) {
                foreach($points as $point) {
                    key_exists($time, $chron) || ($chron[$time] = []);
                    $label = "{$tag} - " . (is_array($point) ? array_key_first($point) : $point);
                    $chron[$time][] = is_array($point) ? [$label => reset($point)] : $label;
                }
            }
        }
        ksort($chron, SORT_NUMERIC);
        return $chron;
    }

    /**
     * not implemented yet
     */
    public static function commitTags(): bool
    {
        // #TODO commit to DB.
        return true;
    }

    /**
     * returns a notice prefix string for the current time delta
     */
    protected static function generateNotice(): string
    {
        $preset = self::init();
        $now = microtime(true);
        $gateTime = $preset ? ($now - self::$microStartTime) : null;
        $gateText = is_null($gateTime) ? 'clock not started' : $gateTime;
        return "{$gateText} ({$now}) - ";
    }

    /**
     * retuns the time the application profiling started.
     * will check a variety of sources for a start time 
     *   if the clock is not known to be started.
     * otherwise starts the clock.
     * #TODO add a callable optional param to start the clock
     */
    protected static function init(): ?float
    {
        if (!is_null(self::$microStartTime)) {
            return self::$microStartTime;
        }
        // if callable($callable) ...
        if (defined('\\DEBUG_START_TIME')) {
            self::$microStartTime = (int) \DEBUG_START_TIME;
            self::$timeSource = 'debug';
            return self::$microStartTime;
        } elseif (defined('\\Saf\\APPLICATION_START_TIME')) {
            self::$microStartTime = (int) \Saf\APPLICATION_START_TIME;
            self::$timeSource = 'saf_app';
            return self::$microStartTime;
        }
        self::$microStartTime = microtime(true);
        self::$timeSource = 'init';
        return null;
    }
}