<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for Time, includes testing insulation intended to replace 
 * direct use of time() and microtime()
 */

declare(strict_types=1);

namespace Saf\Util;

use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
#use Saf\Hash;

class Time
{

    public const string DEFAULT_TIME_ZONE = 'Europe/London';

    public const null MODIFIER_NOW = null;
    public const int MODIFIER_START_TODAY = 1; //#TODO #2.0.0 use native modifier string codes and refactor
    public const int MODIFIER_END_TODAY = 2;
    public const int MODIFIER_START_TOMORROW = 3;
    public const int MODIFIER_START_HOUR = 4;
    public const int MODIFIER_END_HOUR = 5;
    public const int MODIFIER_START_NEXT_HOUR = 6;
    public const int MODIFIER_END_NEXT_HOUR = 7;
    public const int MODIFIER_START_HALFHOUR = 8;
    public const int MODIFIER_END_HALFHOUR = 9;
    public const int MODIFIER_START_NEXT_HALFHOUR = 10;
    public const int MODIFIER_END_NEXT_HALFHOUR = 11;
    public const int MODIFIER_START_QTRHOUR = 12;
    public const int MODIFIER_END_QTRHOUR = 13;
    public const int MODIFIER_START_NEXT_QTRHOUR = 14;
    public const int MODIFIER_END_NEXT_QTRHOUR = 15;
    public const int MODIFIER_START_MINUTE = 16;
    public const int MODIFIER_END_MINUTE = 17;
    public const int MODIFIER_START_NEXT_MINUTE = 18;
    public const int MODIFIER_END_NEXT_MINUTE = 19;
    
    public const int MODIFIER_START_DAY = 20;
    public const int MODIFIER_END_DAY = 21;

    // public const int MODIFIER_START_WEEK = 30;
    // public const int MODIFIER_END_WEEK = 31;

    // public const int MODIFIER_START_MONTH = 40;
    // public const int MODIFIER_END_MONTH = 41;
    
    public const int MODIFIER_ADD_MINUTE = 100;
    public const int MODIFIER_ADD_QRTHOUR = 101;
    public const int MODIFIER_ADD_HALFHOUR = 102;
    public const int MODIFIER_ADD_HOUR = 103;
    public const int MODIFIER_ADD_DAY = 104;    
    public const int MODIFIER_ADD_WEEK = 105;    
    public const int MODIFIER_ADD_MONTH = 106;    
    public const int MODIFIER_ADD_YEAR = 107;
    
    public const int MODIFIER_SUB_MINUTE = 200;
    public const int MODIFIER_SUB_QRTHOUR = 201;
    public const int MODIFIER_SUB_HALFHOUR = 202;
    public const int MODIFIER_SUB_HOUR = 203;
    public const int MODIFIER_SUB_DAY = 204;
    public const int MODIFIER_SUB_WEEK = 205;
    public const int MODIFIER_SUB_MONTH = 206;
    public const int MODIFIER_SUB_YEAR = 207;
    
    public const int MAX_HOUR_STAMP = 86399;
    
    public const int QUANT_MINTUTE = 60;
    public const int QUANT_HALFHOUR = 1800;
    public const int QUANT_HOUR = 3600;
    public const int QUANT_DAY = 86400;
    
    public const string FORMAT_DATE_URL = 'Y-m-d';
    public const string FORMAT_TIME_URL = 'H-i';
    public const string FORMAT_DATETIME_URL = 'Y-m-d/H-i';
    public const string FORMAT_DATETIME_DB = 'Y-m-d H:i:s';
    public const string FORMAT_MONTH_SHORT = 'F';
    public const string FORMAT_MONTH_FULL = 'M';

    const NUMBER_PATTERN_INF = '/^[-]?[0-9]+$/';

    /**
     * configurable global time zone leveraged by all static methods
     */
    protected static string $timeZone = self::DEFAULT_TIME_ZONE;

    /**
     * how many seconds off real time insulation tests against
     */
    protected static int $diff = 0;
    
    /**
     * how many microseconds off time insulation tests against
     */
    protected static int $microDiff = 0;

    /**
     * PSR container invocation support.
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): Object
    {
        $default = 
            defined('\\Saf\\DEFAULT_TIME_ZONE') 
            ? \Saf\DEFAULT_TIME_ZONE 
            : self::DEFAULT_TIME_ZONE;
        $containerConfig = Container::getOptional($container, 'config', []);
        $defaultConfig = ['defaultTimeZone' => $default];
        $config = 
            is_array($containerConfig)
            ? ($containerConfig + $defaultConfig)
            : $defaultConfig;
        self::init($config);
        return $callback();
    }

    /**
     * sets the internal time zone and configures the current PHP environment to match
     */
    public static function init(mixed $globalConfig): void
    {
        $localConfig = Container::getLocalConfig($globalConfig, self::class);
        if (is_array($globalConfig) && key_exists('defaultTimeZone', $globalConfig)) {
            self::$timeZone = $globalConfig['defaultTimeZone'];
            $manageGlobalTimeZone = !key_exists('manageInternalTimeZoneOnly', $localConfig) || !$localConfig['manageInternalTimeZoneOnly'];
            $manageGlobalTimeZone && date_default_timezone_set($globalConfig['defaultTimeZone']);
        }
    }

    /**
     * get the internally configured time zone for all static methods
     */
    public static function getTimeZone(): string
    {
        return self::$timeZone;
    }

    /**
     * offset the time for static methods, used for time insulation (testing).
     * this method accepts a fixed unixy timestamp to calculate the offset at calltime.
     */
    public static function set(int $seconds, int $micro = 0): void
    {
        $now = microtime(true);
        $new = $seconds + ($micro / 1000);
        $offset = $new - $now;
        self::$diff = (int)ceil($offset);
        self::$microDiff = (int)$offset;
        \Saf\Debug::outData([
            'setting debug time',
            $seconds,
            $micro,
            'effective' => [
                time() + self::$diff, date(self::FORMAT_DATETIME_URL . '-s', time() + self::$diff)
            ]
        ]);
    }

    /**
     * offset the timesfor static methods, used for time insulation (testing).
     * this method accepts a relative number of seconds from the actual current time.
     */
    public static function offset(int $seconds, int $micro = 0): void
    {
        self::$diff = $seconds;
        self::$microDiff = $micro;
        \Saf\Debug::outData([
            'offsetting debug time',
            $seconds,
            $micro,
            'effective' => [
                time() + self::$diff, date(self::FORMAT_DATETIME_URL . '-s', time() + self::$diff)
            ]
        ]);
    }

    /**
     * returns the timestamp modified by any internal offset
     * 
     * @param int $now input differential, defualts to now (i.e. the native time())
     * @return int the modified timestamp
     */    
    public static function time(int|float $now = null): int|float
    { //#TODO include microdiff when float is deteted
        if (is_null($now)) {
            $now = time();
        }
        return $now + self::$diff;
    }

    /**
     * returns the current timestamp
     * @return int the modified timestamp
     */    
    public static function now(bool $microtime = false): int|float
    {
        return $microtime ? self::time(microtime(true)) : self::time();
    }

    /**
     * returns the relative time based on $modifier
     */
    public static function relative($modifier)
    {
        return self::modify(self::time(), $modifier);
    }

    /**
     * returns a modified timestamp. if only one param is provided,
     * the first param is used for $modifier and the current (insulated) time() is used.
     * @param int|string $timestamp or used as $modifier if only one param is provided
     * @param ?int $modifier modifier (MODIFIER_* class constant) to apply
     * @return false|int the final calculation is run back
     */
    public static function modify(int|string $time, ?int $modifier = null): false|int
    { //#TODO #2.0.0 needs to factor in DST
        if (is_null($modifier)) {
            $modifier = $time;
            $timestamp = self::time();
        } else if (
            is_null($time)
            || is_string($time) && trim($time) == ''
        ) {
            $timestamp = self::time();
        } elseif (is_string($time)) {
            $timestamp  = is_numeric($time) ? (int)$time : strtotime($time);
        } else {
            $timestamp = (int)$time;
        }//#TODO set a new "epoch" for the min timestamp to differentiate int modifiers
        if (in_array($modifier, [
            self::MODIFIER_END_TODAY,
            self::MODIFIER_START_TODAY,
            self::MODIFIER_START_TOMORROW //#TODO #2.0.0 others as needed
        ])) {
            $timestamp = self::time();
        }

        $min = (int)date('i', $timestamp);
        $hour = (int)date('H', $timestamp);
        $day = (int)date('d', $timestamp);
        $month = (int)date('m', $timestamp);
        $year = (int)date('Y', $timestamp);
        // #TODO 't' last day of month
        $nextMin = $min < 59 ? $min + 1 : 0;
        $nextHour = $hour < 23 ? $hour + 1 : 0;
        $nextDay = $day < (int)date('t') ? $day + 1 : 1;
        $nextMonth =  $month < 12 ? $month + 1 : 1;
        $nextYear = $year + 1;    
        $prevMin = $min > 0 ? $min - 1 : 59;
        $prevHour = $hour > 0 ? $hour -1 : 23;
        $prevDay =
            $day > 1
            ? $day - 1
            : (int)date('t',
                strtotime(
                    $year. '-'
                    .  str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-'
                    . ( str_pad((string)$day, 2, '0', STR_PAD_LEFT))
                    . 'T00:00'
                ) - 1
            );
        $prevMonth =  $month > 1 ? $month - 1 : 12;
        $prevYear = $year - 1;    
        $modSec = '00';
        $modMin = $min;
        $modHour = $hour;
        $modDay = $day;
        $modMonth = $month;
        $modYear = $year;
        switch($modifier){ //#TODO #2.0.0 build out in long form and then refactor once testing framework is in place
            case self::MODIFIER_START_HALFHOUR :
                $modMin =
                    $min < 30
                    ? 0
                    : 30;
                break;
            case self::MODIFIER_START_NEXT_HALFHOUR :
                $modMin = 
                    $min < 30
                    ? 30
                    : 0;
                $modHour =
                    $min < 30
                    ? $hour
                    : $nextHour;
                $modDay =
                    $modHour >= $hour
                    ? $day
                    : $nextDay;
                $modMonth =
                    $modDay >= $day
                    ? $month
                    : $nextMonth;
                $modYear =
                    $modMonth >= $month
                    ? $year
                    : $nextYear;
                break;
            case self::MODIFIER_ADD_HALFHOUR :
                $modMin = 
                    $min < 30
                    ? $min + 30
                    : $min - 30;
                $modHour =
                    $min < 30
                    ? $hour
                    : $nextHour;
                $modDay =
                    $modHour >= $hour
                    ? $day
                    : $nextDay;
                $modMonth =
                    $modDay >= $day
                    ? $month
                    : $nextMonth;
                $modYear =
                    $modMonth >= $month
                    ? $year
                    : $nextYear;
                break;
            case self::MODIFIER_START_TOMORROW :
                $modMin = 0;
                $modHour = 0;        
                $modDay = $nextDay;
                $modMonth =
                    $modDay > $day
                    ? $month
                    : $nextMonth;
                $modYear =
                    $modMonth >= $month
                    ? $year
                    : $nextYear;
                break;
            case self::MODIFIER_ADD_DAY:
                $modDay = $nextDay;
                $modMonth =
                    $modDay > $day
                    ? $month
                    : $nextMonth;
                $modYear =
                    $modMonth >= $month
                    ? $year
                    : $nextYear;
                break;
            case self::MODIFIER_SUB_DAY:
                $modDay = $prevDay;
                $modMonth =
                    $modDay < $day
                    ? $month
                    : $prevMonth;
                $modYear =
                    $modMonth <= $month
                    ? $year
                    : $prevYear;
                break;
            case self::MODIFIER_START_DAY :
            case self::MODIFIER_START_TODAY :
                $modMin = 0;
                $modHour = 0;
                break;
            case self::MODIFIER_END_DAY:
            case self::MODIFIER_END_TODAY:
                $modSec = 59;
                $modMin = 59;
                $modHour = 23;
                break;
            case self::MODIFIER_ADD_WEEK:
                $return = $timestamp;
                for ($i = 0; $i < 7; $i++) {
                    $return = self::modify($return, self::MODIFIER_ADD_DAY);
                }
                return $return;
                break;
            case self::MODIFIER_SUB_WEEK:
                $return = $timestamp;
                for ($i = 0; $i < 7; $i++) {
                    $return = self::modify($return, self::MODIFIER_SUB_DAY);
                }
                return $return;
                break;
            case self::MODIFIER_ADD_YEAR :
                $modYear = $nextYear;
                break;
            case null:
            default:
                return $timestamp;
        }
        $modMin = str_pad((string)$modMin, 2, '0', STR_PAD_LEFT);
        $modHour = str_pad((string)$modHour, 2, '0', STR_PAD_LEFT);
        $modDay = str_pad((string)$modDay, 2, '0', STR_PAD_LEFT);
        $modMonth = str_pad((string)$modMonth, 2, '0', STR_PAD_LEFT);
        return strtotime("{$modYear}-{$modMonth}-{$modDay}T{$modHour}:{$modMin}:{$modSec}");
    }

    /**
     * parse a string as a time delta or time string, returning the resulting timestamp.
     * uses the insulated time unless $insulated is false.
     */
    public static function parse(?string $time, ?bool $insulated = true): ?int
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        } elseif (is_numeric($time)) {
            return ($insulated ? self::time() : time()) + intval($time);
        } else {
            $interpreted = strtotime($time, $insulated ? self::time() : time());
            return is_int($interpreted) ? $interpreted : null;
        }
    }
    
    /**
     * detects valid timestamps
     * @param string $time
     */
    public static function isTimeStamp(null|int|string|float $time): bool
    {
        return
            !is_null($time)
            && (
                is_int($time)
                || is_float($time)
                || preg_match(self::NUMBER_PATTERN_INF, (string)$time)
            );
        //#TODO #2.0.0 does not factor in max int size
    }
    
    /**
     * detects valid hour stamps 
     * (integer in seconds relative to start of the day)
     * if $allowOverflow, allow it to be relative to that many  additional days 
     * (TRUE or 1 == 2 days)
     * @param string $string
     * @param bool $allowOverflow
     * @return boolean
     */
    public static function isHourStamp(mixed $string, $allowOverflow = false)
    {
        $allowOverflow =
            $allowOverflow
            ? (((self::MAX_HOUR_STAMP + 1) * ($allowOverflow + 1)) - 1)
            : self::MAX_HOUR_STAMP;
        $numberPattern = 
            $allowOverflow > self::MAX_HOUR_STAMP 
            ? '/^[0-9]{1,11}$/' 
            : '/^[0-9]{1,5}$/';
        $match = !is_null($string) && preg_match($numberPattern, (string)$string);
        return $match && (int)$string <= $allowOverflow;
    }
    
    /**
     * converts H:i and H-i strings into hour stamps
     * skips to after the T in typical WDDX timestamp
     * this is a 24 hour format, it will not look for am/pm
     * @param string $string
     */
    public static function hourStringToStamp(string $string, bool $roundUpSeconds = true): int
    { // #TODO add am/pm support
        $tPos = strpos($string, 'T');
        if ($tPos !== false) {
            $string = substr($string, $tPos + 1); // #TODO exclude parts after '+' ...
        }
        $parts = explode(':', str_replace('-',':', $string), 3);
        if (!array_key_exists(1, $parts)) {
            $parts[1] = 0;
        } else {
            $parts[1] = min(59, $parts[1]);
        }
        if (array_key_exists(2, $parts)) {
            $parts[2] = substr($parts[2], 0, 2);
            if ((int)$parts[2] == 59) {
                $parts[1] += 1;
            }
        }
        $parts[0] = min(23, $parts[0]);
        $calc = min(
            self::MAX_HOUR_STAMP,
            ((int)$parts[0] * 3600) 
                + (array_key_exists(1, $parts) ? ((int)$parts[1] * 60) : 0)
        );
        //Saf_Debug::outData(array('hourStamp',$string,$parts[0],$parts[1],$calc)); //#DEBUG #1.5.0
        return $calc; 
    }

    /**
     * get the amount of time in seconds insulation testing is configured to offset method results
     */
    public static function getOffset(): int
    {
        return self::$diff;
    }

    /**
     * get the amount of microseconds (only) insulation testing is configured to offset method results
     */
    public static function getMicroOffset(): int
    {
        return self::$microDiff;
    }

    /**
     * Gets a PHP string representation of a numerical month (e.g. 1 = January, 12 = December)
     * $format accepts date() options https://www.php.net/manual/en/datetime.format.php
     */
    public static function lookupMonth(int|string $number, ?string $format = self::FORMAT_MONTH_FULL): string
    {
        $date = '2000-' . str_pad((string)$number, 2, '0', STR_PAD_LEFT) . '-15';
        return date($format, strtotime($date));
    }

    /**
     * get a URI friendly representation of a date: YYYY-MM-DD
     */
    public static function timeStampToDateUri(?int $stamp = null): string
    {
        if (is_null($stamp)) {
            $stamp = self::modify(self::MODIFIER_START_TODAY);
        }
        return date(self::FORMAT_DATE_URL, $stamp);
    }

    /**
     * get a human readable representation of a time of day: HH-MM 
     * from an intenger offset $hourStamp in seconds 
     * from the start of the day ($dateStamp, or today if not provided).
     * $dateStamp can be any timestamp on a day.
     */
    public static function hourStampToString(int $hourStamp, ?int $dateStamp = null, ?string $format = 'g:i A'): string
    {
        if (is_null($dateStamp)) {
            $dateStamp = self::modify(self::MODIFIER_START_TODAY);
        }
        return date($format, $hourStamp + $dateStamp);
    }

    /**
     * get a URI friendly representation of a time of day: HH-MM
     * from an intenger offset $hourStamp in seconds
     */
    public static function hourStampToUri(int $hourStamp, ?int $dateStamp = null): string
    {
        if (is_null($dateStamp)) {
            $dateStamp = self::modify(self::MODIFIER_START_TODAY);
        }
        return date(self::FORMAT_TIME_URL, $hourStamp + $dateStamp);
    }

    /**
     * returns the integer hourstamp (seconds from the start of the day) that any shift forward
     * due to Daylight Savings happens, or null if none.
     * *0 is a distinct result from null* and does indicate a DST shift.
     * If passed an array of timestamps, an array of results indexed by the timestamp 
     * (not truncated to datestamp) will be returned.
     */
    public static function detectSpringForward(array|int $date): null|int|array
    {
        if (is_array($date)) {
            $result = [];
            foreach($date as $d) {
                $spring = self::detectSpringForward($d);
                if(!is_null($spring)) {
                    $result[$d] = $spring;
                }
            }
            return $result;
        }
        $date = self::modify($date, self::MODIFIER_START_DAY);
        $dateIsDaylight = date('I', $date);
        for ($i = 0; !$dateIsDaylight && $i < self::QUANT_DAY; $i += self::QUANT_HOUR) {
            if (date('I', $i + $date)) {
                return $i;
            }
        }
        return null;
    }

    /**
     * returns the integer hourstamp (seconds from the start of the day) that any shift back
     * due to Daylight Savings happens, or null if none.
     * *0 is a distinct result from null* and does indicate a DST shift.
     * If passed an array of timestamps, an array of results indexed by the timestamp 
     * (not truncated to datestamp) will be returned.
     */
    public static function detectFallBack(array|int $date): null|int|array
    {
        if (is_array($date)) {
            foreach($date as $d) {
                $fall = self::detectSpringForward($d);
                if(!is_null($fall)) {
                    $d + $fall;
                    //return array($d => $fall);
                }
            }
            return null;
        }
        $date = self::modify($date, self::MODIFIER_START_DAY);
        $dateIsDaylight = date('I', $date);
        for ($i = 0; $dateIsDaylight && $i < self::QUANT_DAY; $i += self::QUANT_HOUR) {
            if (!date('I', $i + $date)) {
                return $i;
            }
        }
        return null;
    }

    // #TODO single detectShift method for fallBack (-) and springForward (+)

    /**
     * get an hourStamp (truncated to date but not hour) for any unix timestamp
     */
    public static function timeToHour(int $time): int
    {
        return $time - self::modify(self::MODIFIER_START_DAY, $time);
    }

    /**
     * return a type-safe string for strict methods
     * if the value can't be safely coerced to a string, return $default
     */
    public static function safeString(mixed $maybeString, ?string $default = ''): string
    {
        return is_string($maybeString) ? $maybeString : $default;
    }

    /**
     * return a type-safe integer timestamp for strict methods 
     * if the value can't be safely coerced to a string, return $default
     */
    public static function safeInt(mixed $maybeInt, ?int $default = 0): int
    {
        return is_int($maybeInt) ? $maybeInt : $default;
    }

}
