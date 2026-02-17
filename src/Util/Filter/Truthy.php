<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Filter for boolean like strings
 */

namespace Saf\Util\Filter;

use Saf\Filter;

require_once(__DIR__ . '/Filter.php');

class Truthy extends Filter
{
    protected static $truthyStrings = [
        'y', 'yes', '1', 't', 'true'
    ];
    public static function filter($value): bool
    {
        if(is_bool($value)){
            return $value;
        }

        if(is_string($value)){
            return in_array(strtolower(trim($value)), self::$truthyStrings);
        }
        return (bool)$value;
    }
}