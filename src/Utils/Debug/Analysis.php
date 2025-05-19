<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Introspection Utility for Saf\Debug
 */

namespace Saf\Utils\Debug;

use Saf\Debug;

class Analysis 
{

    public const int DEFAULT_MEMORY_LIMIT = 1000000; // 1M
    // DEFAULT_MAX_SIZE = ?
    public const int MAX_MEMORY_SHARE = 4; // one quarter
    public const int SIZE_K = 1000;
    public const int SIZE_M = self::SIZE_K * 1000;
    public const int SIZE_G = self::SIZE_M * 1000;
    public const int DEFAULT_MAX_DEPTH = 10;

    protected static array $objectReferenceBuffer = [];

    public static function data(mixed $message, ?int $maxDepth = self::DEFAULT_MAX_DEPTH, ?int $maxSize = null): string
    {
        $maxSize ??= self::autoMaxSize();
        $type = gettype($message);
        switch($type) {
            case 'object':
                return self::dataObject($message, $maxDepth, $maxSize);
            case 'array':
                return self::dataArray($message, $maxDepth, $maxSize);
            default:
                return "{$type}:{$message}";
        }
    }

    public static function dataObject(object $message, ?int $maxDepth = self::DEFAULT_MAX_DEPTH, ?int $maxSize = null): string
    {
        $maxSize ??= self::autoMaxSize();
        $internal = '';
        $class = get_class($message);
        //#TODO handle Error and Exception types
        return "Object:{$class}{$internal}";
    }

    public static function dataArray(array $message, ?int $maxDepth = self::DEFAULT_MAX_DEPTH, ?int $maxSize = null): string
    {
        $maxSize ??= self::autoMaxSize();
        $count = count($message);
        $keys = array_keys($message);
        $keySize = self::keySize($keys);
        $nearLimit = $keySize > ($maxSize / self::MAX_MEMORY_SHARE);
        if  ($keySize > $maxSize) {
            $contents = '[...]';
        } else if ($nearLimit || $maxDepth <= 1) {
            $allowedKeys = $nearLimit ? $keys : array_slice($keys, 0, $count / self::MAX_MEMORY_SHARE);
            $contents = '[' . implode(', ', $allowedKeys) . ', ...]';
        } else {
            $expose = '';
            foreach($message as $key => $value) {
                $safeValue = self::data($value, $maxDepth - 1, $maxSize - $keySize);
                $expose .= ($expose ? ', ' : '') . "{$key} => {$safeValue}";
            }
            $contents = ":[{$expose}]";
        }
        return "Array:{$count}{$contents}";
    }

    public static function unitConvert(int|string $quantity): int
    {
        if (is_string($quantity)) {
            $unit = substr($quantity, -1, 1);
            switch ($unit) {
                case 'K':
                    return (int)substr($quantity, 0, -1) * self::SIZE_K;
                case 'M':
                    return (int)substr($quantity, 0, -1) * self::SIZE_M;
                case 'G':
                    return (int)substr($quantity, 0, -1) * self::SIZE_G;
                default:
                    if (!is_numeric($unit)) {
                        return -1;
                    }
            }
        }
        return $quantity;
    }

    public static function autoMaxSize(): int
    {
        $max = self::unitConvert(ini_get('memory_limit'));
        if ( $max == -1) {
            $max == self::DEFAULT_MEMORY_LIMIT;
        }
        $available = $max - memory_get_usage();
        return $available / self::MAX_MEMORY_SHARE;
    }

    public static function keySize(array $keys): int
    {
        $size = 0;
        foreach($keys as $string) {
            $size += strlen($string);
        }
        return $size;
    }
}