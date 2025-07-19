<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility functions for handling HTTP mime types
 */

namespace Saf\Util\Http;

use Psr\Http\Message\ServerRequestInterface;

class Mime
{

    public const string HEADER_VALUE_XMLHTTPREQ = 'XMLHttpRequest';

    public const string HEADER_ACCEPT = 'accept';
    public const string HEADER_XREQW = 'x-requested-with';

    public const string TYPE_WILD = '*/*';

    public const string TYPE_HTML = 'text/html';
    public const string TYPE_XHTML = 'application/xhtml+xml';
    public const string TYPE_XML = 'application/xml';

    public const string TYPE_JS = 'text/javascript';
    public const string TYPE_JSON = 'application/json';

    public const array AJAX_TYPES = [self::TYPE_JSON, self::TYPE_JS];
    public const array HTML_TYPES = [self::TYPE_HTML, self::TYPE_XHTML, self::TYPE_XML];

    /**
     * checks request for the best (single) match
     */
    public static function detectClientFormat(ServerRequestInterface $request, ?array $options = null): string
    {
        $accepts = $request->getHeader(self::HEADER_ACCEPT);
        $flat = [];
        foreach($options as $option) { //#TODO use a Hash:: method...
            if (is_array($option)) {
                foreach($option as $sub) {
                    $flat[] = $sub;
                }
            } else {
                $flat[] = $sub;
            }
        }
        return self::match($accepts, $flat) ?: self::TYPE_HTML;
        // is_array($useHtml) && ($useHtml = array_pop($useHtml));
        // $htmlWeight = strpos(self::TYPE_HTML, $useHtml); //#TODO ... we implemented a better way to do this ...somewhere
        // $jsonWeight = strpos(self::TYPE_JSON, $useHtml);
        // $useHtml = $jsonWeight === false || ($htmlWeight !== false && $htmlWeight < $jsonWeight);
    }

    /**
     * checks request headers to determine if an Ajax (JSON) response is most appropriate
     */
    public static function detectAjax(ServerRequestInterface $request): bool
    {
        return
            in_array(self::HEADER_VALUE_XMLHTTPREQ, $request->getHeader(self::HEADER_XREQW))
            || self::prefers($request->getHeader(self::HEADER_ACCEPT), self::AJAX_TYPES, self::HTML_TYPES);
    }

    /**
     * check the accept headers for options in $more that are prefered over $less
     */
    public static function prefers(array $acceptArray, string|array $more, string|array $less): bool
    {
        $bestMatch = self::TYPE_WILD;
        $bestMatchWeight = 0;
        foreach($acceptArray as $headerLine){
            $headerOptions = explode(',', $headerLine);
            foreach($headerOptions as $format) {
                $weighted = explode(';', trim($format));
                $format = trim($weighted[0]);
                $weight = count($weighted) > 1 ? floatval(substr(trim($weighted[1]), 2)) : null;
                if (is_null($weight)) {
                    if (in_array($format, $more)) {
                        return true;
                    } elseif (in_array($format, $less)) {
                        return false;
                    }
                } elseif ($weight > $bestMatchWeight) {
                    $bestMatch = $format;
                    $bestMatchWeight = 0;
                }
            }
        }
        return in_array($bestMatch, $more);
    }

    /**
     * return the highest weight option
     */
    public static function match(array $acceptArray, string|array $options): ?string
    {
        $bestMatch = self::TYPE_WILD;
        $bestMatchWeight = 0;
        foreach($acceptArray as $headerLine){
            $headerOptions = explode(',', $headerLine);
            foreach($headerOptions as $format) {
                $weighted = explode(';', trim($format));
                $format = trim($weighted[0]);
                $weight = count($weighted) > 1 ? floatval(substr(trim($weighted[1]), 2)) : null;
                if (is_null($weight)) {
                    if (in_array($format, $options)) {
                        return $format;
                    }
                } elseif ($weight > $bestMatchWeight) {
                    $bestMatch = $format;
                    $bestMatchWeight = 0;
                }
            }
        }
        return in_array($bestMatch, $options) ? $bestMatch : null;
    }

}

