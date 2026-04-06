<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Introspection Utility for Saf\Debug
 */

namespace Saf\Util\Debug;

use Saf\Debug;

class Analysis //#TODO implement as trait?
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
            case 'NULL':
                return 'NULL';
            case 'boolean':
                $label = $message ? 'true' : 'false';
                return "{$type}:{$label}";
            case 'string':
                $length = strlen($message);
                if ($length > $maxSize / self::MAX_MEMORY_SHARE) {
                    $message = substr($message, $maxSize / self::MAX_MEMORY_SHARE) . '...';
                }
                return "{$type}:{$length}:[{$message}]";
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

    public static function escapeTraceStrings(string $s, ?int $max = 512): string
    {
        $main = substr($s, 0, $max);
        $suffix = strlen($s) > $max ? '[...]' : '';
        return"'{$main}'{$suffix}";
    }

    public static function escapeTraceArray(array $a, null|int|string $behavior = 16): string
    {
        $out = '';
        $prefix = '';
        $count = 1;
        $max = is_int($behavior) ? $behavior : 16;
        foreach($a as $index => $value) {
            if (is_string($index)) {
                $index = "'{$index}'";
            }
            $rendered = self::renderArg($value, is_int($behavior) ? Debug::TRACE_DIGEST : $behavior);
            $out .= "{$prefix}{$index} => {$rendered}";
            $prefix = ', ';
            $count++;
            if ($max >= 0 && $count > $max) {
                $out .= "{$prefix}...";
                break;
            }
        }
        return $out;
    }

    /**
     * renders a debug_backtrace() or Throwable's getTrace() as a journaled multi-line string
     */
    public static function renderTrace(array|\Throwable $trace, ?string $behavior = Debug::TRACE_DIGEST): string
    {
        if (is_a($trace, \Throwable::class)) {
            $trace = $trace->getTrace();
        }
        $standardEol = "\n";
        $out = '';
        $count = count($trace);
        foreach($trace as $index => $point) {
            //Debug::audit($point);
            $out .= self::renderTraceLine($point, $behavior);
        }
        $out .= "#{$count} {main} {$standardEol}";
        return $out;
    }

    /**
     * renders a single entry (array) from a debug_backtrace() or Throwable's getTrace() as a journaled string
     */
    public static function renderTraceLine(array $point, ?string $behavior = Debug::TRACE_DIGEST): string
    {
        $standardEol = "\n";
        $line =
            key_exists('file', $point)
            ? "{$point['file']}({$point['line']})"
            : '';
        $context = 
            key_exists('function', $point)
            ? (
                ': '
                . (key_exists('class', $point) ? "{$point['class']}{$point['type']}" : '')
                . "{$point['function']}"
            ) : '';
        $argCount = key_exists('args', $point) && $point['args'] ? count($point['args']) : 0;
        $env =
            key_exists('args', $point) && $behavior != Debug::TRACE_BARE
            ? ('(' . self::renderArgList($point['args'], $behavior) . ')')
            : ($argCount ? "(...[{$argCount}])" : '()');
        return "#{$index} {$line}{$context}{$env}{$standardEol}";
    }

    public static function renderArg(mixed $value, string $behavior = Debug::TRACE_DIGEST): string
    {
        $type = gettype($value);
        $representation = '';
        switch($type) {
            case 'integer':
                $representation = (string)$value;
                return "(int){$representation}";
            case 'boolean':
                $representation = $value ? 'true' : 'false';
                return "(bool){$representation}";
            case 'double':
                $representation = (string)$value;
                return "(float){$representation}";
            case 'string':
                $type = "{$type}/" . strlen($value);
                $escValue = self::escapeTraceStrings($value);
                return "({$type})$escValue";
            case 'array':
                $type = "{$type}/" . count($value); //#TODO is numeric/subtype
                $escValue = $behavior == Debug::TRACE_BARE ? '' : self::escapeTraceArray($value);
                return "($type)[{$escValue}]";
            case 'object':
                return $value::class;
            case 'resource':
            case 'callable':
               return "[{$type}]";
            case 'NULL':
                return 'null';
            default:
                return "({$type})[xxx]";
        }
    }

    public static function renderThrowable(\Throwable $e, ?int $pad = 0): string
    {
        $padding = str_pad('', $pad, ' ');
        $innerPadding = '  ';
        $out = "{$padding}(".PHP_EOL;
        $code = $e->getCode() ? ":{$e->getCode()}" : '';
        $class = get_class($e);
        $out .= "{$padding}{$innerPadding}{$class}{$code}:{$e->getFile()}({$e->getLine()}):{$e->getMessage()}".PHP_EOL;
        if ($e->getPrevious()) {
            $out .= self::renderThrowable($e->getPrevious(), $pad + str_len($innerPadding)).PHP_EOL;
        }
        $out .= "{$padding}{$innerPadding}{".PHP_EOL;
        $trace = explode(PHP_EOL, $e->getTraceAsString());
        foreach($trace as $line) {
            $out .= "{$padding}{$innerPadding}{$innerPadding}{$line}".PHP_EOL;
        }
        $out .= "{$padding}{$innerPadding}}".PHP_EOL;
        $out .= "{$padding})";
        return $out;
    }

    public static function renderArgList(array $args, $behavior = Debug::TRACE_DIGEST): string
    {
        $out = '';
        $prefix = '';
        foreach($args as $argKey => $argValue) {
            $representation = self::renderArg($argValue, $behavior);
            $out .= "{$prefix}{$representation}";
            $prefix = ', ';
        }
        return $out;
    }
}