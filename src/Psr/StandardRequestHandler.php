<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for PSR RequestHandler implementations
 */

namespace Saf\Psr;

class StandardRequestHandler {

    public const string DEFAULT_REQUEST_SEARCH = 'APG';
    public const string STACK_ATTRIBUTE = 'resourceStack';
    public const string URI_PATH_DELIM = '/';
    public const string CALLBACK_OPTIONS = 'options';


    public static function defaultRequestSearchOrder(): string
    {
        return self::DEFAULT_REQUEST_SEARCH;
    }

    public static function stackAttributeField(): string
    {
        return self::STACK_ATTRIBUTE;
    }

    public static function uriPathDelimiter(): string
    {
        return self::URI_PATH_DELIM;
    }

}