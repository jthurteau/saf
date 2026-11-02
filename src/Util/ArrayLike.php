<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for list (array) manipulation
 */

namespace Saf\Util;

use Saf\Exception\NotAnArray;

require_once(dirname(__DIR__).'/Exception/NoDefault.php');
require_once(dirname(__DIR__).'/Exception/NotAnArray.php');

trait ArrayLike
{

    public static function traversable(mixed $maybe): bool
    {
        return
            is_array($maybe)
            || (is_object($maybe) && is_a($maybe, 'Traversable'));
    }

    public static function arrayAccess(mixed $maybe): bool
    {
        return
            is_array($maybe)
            || (is_object($maybe) && is_a($maybe, 'ArrayAccess'));
    }

    /**
     * Tests for array compataiblity
     * @throws Saf\NotAnArray
     * @param mixed $array to test
     */
    public static function assert($array) : bool
    {
        if ( //#TODO PHP8 allows inline thrown
            !is_array($array) 
            && !($array instanceof \ArrayAccess)
            && !($array instanceof \Traversable)
        ){
            throw new NotAnArray();
        }
        return true;
    }
}