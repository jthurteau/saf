<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Filter for html like strings
 */

namespace Saf\Util\Filter;

use Saf\Filter;

require_once(__DIR__ . '/Filter.php');

class Html extends Filter
{
    public const int MAX_TAG_LENGTH = 64;
    public const array ALPHAS = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 
        'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
        'u', 'v', 'w', 'x', 'y', 'z',
    ];
    public const array NUMS = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    ];

    /**
     * not implemented. #TODO filter the passed value as a valid simplified subset of HTML
     */
    public static function filter($value): string
    {
        return (string) $value;
    }

    public static function tagSafe(mixed $tag): string
    {
        return self::assertStartsAlpha(self::filterString(
            array_merge(self::ALPHAS, self::NUMS), 
            strtolower(substr(trim((string) $tag), 0, self::MAX_TAG_LENGTH))
        ));
    }

    public static function assertStartsAlpha(string $string): string
    {
        return in_array(substr($string, 0, 1), self::ALPHAS) ? $string : "t{$string}";
    }

    public static function filterString(string|array $filter, string $string): string
    {
        is_array($filter) || ($filter = str_split($filter));
        $result = '';
        foreach(str_split($string) as $char) {
            in_array($char, $filter) && ($result .= $char);
        }
        return $result;
    }
}