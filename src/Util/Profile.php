<?php 

/**
 * Utility class for profile debug output
 */

declare(strict_types=1);

namespace Saf\Util;

use Saf\Debug;
use Saf\Util\Debug\Ui as DebugUi;
use Saf\Util\Time;

class Profile
{

    protected static $microStartTime = null;
    protected static $timeSource = null;

    protected static $taggedSteps = [];

    public static function ping($data, null|string|array $tag = null): void
    {
        $notice = self::generateNotice();
        $message =  $notice . Debug::introspectData($data);
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'ping', $data);
    }

    public static function in(string|array $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? $tag : implode(', ', $tag);
        $message =  "{$notice} entry for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'in');
    }

    public static function out(string|array $tag): void
    {
        $notice = self::generateNotice();
        $tags = is_array($tag) ? implode(', ', $tag) : $tag;
        $message =  "{$notice} exit for: {$tags}";
        Debug::out($message, Debug::LEVEL_PROFILE);
        $tag && self::tag($tag, 'out');
    }

    protected static function tag(string|array|\Traversable $tags, string $type, ?array $data = null): float
    {
        $momento = is_null($data) ? [$type] : [$type => $data];
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

    public static function getStartTime(): float
    {
        return self::init() ?? self::$microStartTime;
    }

    public static function getRunTime(): float
    {
        return microtime(true) - self::getStartTime();
    }

    /**
     * return a list of matching tagged steps, or all
     */
    public static function getTags(null|string|array|\Traversable $tags = null): array
    {
        return 
            is_null($tags) 
            ? self::$taggedSteps 
            : (function($t) {
                    $selected = [];
                    foreach(is_string($t) ? [$t] : $t as $v) {
                        key_exists($v, self::self::$taggedSteps) && ($selected[] = $v);
                    }
                    return $selected;
                }
            )($tags);
    }

    public static function commitTags(): bool
    {
        // #TODO commit to DB.
        return true;
    }

    protected static function generateNotice(): string
    {
        $preset = self::init();
        $now = microtime(true);
        $gateTime = $preset ? ($now - self::$microStartTime) : null;
        $gateText = is_null($gateTime) ? 'clock not started' : $gateTime;
        return "{$gateText} ({$now}) - ";
    }

    protected static function init(): ?float
    {
        if (!is_null(self::$microStartTime)) {
            return self::$microStartTime;
        }
        if (defined('DEBUG_START_TIME')) {
            self::$microStartTime = DEBUG_START_TIME;
            self::$timeSource = 'debug';
            return self::$microStartTime;
        }
        if (defined('Saf\APPLICATION_START_TIME')) {
            self::$microStartTime = Saf\APPLICATION_START_TIME;
            self::$timeSource = 'saf_app';
            return self::$microStartTime;
        }
        self::$microStartTime = microtime(true);
        self::$timeSource = 'init';
        return null;
    }
}