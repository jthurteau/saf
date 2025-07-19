<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for Dogwood Error handling
 */

namespace Saf\Util;

use Saf\Debug;

abstract class Errors {

    protected const int MAX_DEPTH = 8;

    public static function chainErrorResponse(\Throwable $e, ?int $maxDepth = self::MAX_DEPTH): array
    {
        $previousReport =
            self::isDebugging() && $e->getPrevious() && $maxDepth
            ? ['previous' => self::chainErrorResponse($e->getPrevious(), --$maxDepth)]
            : [];
        return [
            'message' => $e->getMessage(),
            'location' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'type' => $e::class,
            'code' => $e->getCode(),
            'debugging' => self::isDebugging(),
        ] + $previousReport;
    }

    public static function handleOutgoingException(string|array $response, \Throwable $e, ?array $additional = []): array
    {
        $errorReport =
            self::isDebugging()
            ? ['error' => self::chainErrorResponse($e)]
            : [];
        $base =
            is_array($response)
            ? $response
            : [
                'success' => false,
                'message' => $response
            ];
        // \Saf\Debug::halt($base + $errorReport, $base, $errorReport);
        return $additional + $base + $errorReport;
    }

    public static function isDebugging(): bool
    {
        return Debug::isEnabled();
    }
}