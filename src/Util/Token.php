<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for token indexed data
 */

declare(strict_types=1);

namespace Saf\Util;

use Saf\Hash;

class Token
{

    public const string DEFAULT_DELIM = '-';

    public const string TYPE_LINK = 'link';
    public const string TYPE_DATA = 'data';
    public const string TYPE_TAG = 'tag';
    public const string DATA_ID = 'id';

    public const array LEAF_TYPES = [
        self::TYPE_LINK,
        self::TYPE_DATA,
    ];


    /**
     * returns the default token delim (-), 
     * unless an object with getTokenDelim is provided.
     * getTokenDelim() must return a string
     */
    public static function getDelim(?object $instance = null): string
    {
        return 
            $instance && method_exists($instance, 'getTokenDelim') 
            ? $instance->getTokenDelim()
            : self::DEFAULT_DELIM;
    }

    /**
     * returns an array of strings, optional token first:
     * size 1 if no token, size two if no token
     */
    public static function split(string $token, ?object $source = null): array
    {
        return explode(self::getDelim($source), $token, 2);
    }

    /**
     * returns the body (identifier) of a tokened identifier
     */
    public static function detoken(string $token, ?object $source = null): string 
    {
        $parts = self::split($token, $source);
        return count($parts) > 1 ? $parts[1] : $parts[0];
    }

    /**
     * returns the prefix (token) of a tokened identifier
     */
    public static function token(string $token, ?object $source = null): ?string 
    {
        $parts = self::split($token, $source);
        return count($parts) > 1 ? $parts[0] : null;
    }

    /**
     * returns the source name, if the token is for a link
     */
    public static function source(string $token): ?string
    {
        return 
            self::token($token) == self::TYPE_LINK
            ? self::detoken($token)
            : null;
    }

    /**
     * returns the data name, if the token is for data
     */
    public static function data(string $token): ?string
    {
        return 
            self::token($token) == self::TYPE_DATA
            ? self::detoken($token)
            : null;
    }

    /**
     * 
     */
    public static function ize(string $token, string $data, ?object $source = null): string
    {
        return $token . self::getDelim($source) . $data;
    }

}