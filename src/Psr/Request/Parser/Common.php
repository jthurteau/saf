<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Trait for PSR RequestHandler implementations
 */

declare(strict_types=1);

namespace Saf\Psr\Request\Parser;

use Psr\Http\Message\ServerRequestInterface;
use Saf\Psr\StandardRequestHandler;

trait Common
{

    /**
     * Auto extract a request param from one or more sources, 
     * substituting optional default if not present.
     * $map of sources will be searched iteratively, and the first match returned.
     * $map can be a single integer or string (auto converted to single-item array)
     * integer $map values are a shortcut for ['stack' => <int>]
     * string $map values are shortcut for ['request' => <string>]
     * the 'request' facet searches attributes, post, and get in that order
     * alternatively any order of some or all of the letters: a p g
     * can be specified for a custom search order of the request
     * @param mixed $sources <string>, <int>, array indicating one or more sources
     * @param Psr\Http\Message\ServerRequestInterface $request request object to use
     * @param mixed $default value to return if no match is found, defaults to <null>
     * #TODO #2.1.0 add option for each source to be an array so more than one value in each can be searched
     */
    protected function extractParam($map, ServerRequestInterface $request, $default = null)
    {
        //is_null($request) && ($request = $this->getRequest());
        if (is_null($request)) {
            return $default;
        }
        if (!is_array($map)) {
            $map = is_int($map) ? ['stack' => $map] : ['request' => $map];
        }

        foreach($map as $source => $index) {
            $branchResult = null;
            //#TODO handle index as array (multiple searches)
            if (is_int($source)) {
                $source = is_int($index) ? 'stack' : 'request';
            }
            $stringIndex = (string)$index;
            switch ($source) {
                case 'stack' :
                    $stack = self::getResourceStack($request);
                    if (key_exists($index, $stack) && '' !== $stack[$index] ) {
                        $branchResult =  $stack[$index];
                    }
                    break;
                case 'attribute' :
                    $branchResult = self::requestSearch($request, "A:{$stringIndex}");
                    break;
                case 'post' :
                    $branchResult = self::requestSearch($request, "P:{$stringIndex}");
                    break;
                case 'get' :
                    $branchResult = self::requestSearch($request, "G:{$stringIndex}");
                    break;
                // case 'session' : //#TODO #2.1.0 deep thought on if this should be allowed 
                //     if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
                //         return $_SESSION[$index];
                //     }
                //     break;
                case 'request' :
                    $defaultSearch = StandardRequestHandler::DEFAULT_REQUEST_SEARCH;
                    $branchResult = self::requestSearch($request, "{$defaultSearch}:{$stringIndex}");
                    break;
                default:
                    $branchResult = self::requestSearch($request, "{$source}:{$stringIndex}");
            }
            if (!is_null($branchResult)) {
                return $branchResult;
            }
        }
        return $default;
    }

	/**
	 * Auto extract a request param from one or more sources, 
     * substituting optional default if not present.
	 * $sources will be searched iteratively, and the first match returned.
	 * $sources can be an integer as a shortcut for array('stack' => <int>)
	 * $sources can be a string as a shortcut for array('request' => <string>)
	 * the 'request' facet searches attributes, post, and get in that order
     * alternatively any order of some or all of the letters: a p g
     * can be specified for a custom search order of the request
	 * @param mixed $sources string, int, array indicating one or more sources
	 * @param Psr\Http\Server\RequestHandlerInterface $request request object to use
	 * @param mixed $default value to return if no match is found, defaults to NULL
	 * #TODO #1.5.0 add option for each source to be an array so more than one value in each can be searched
	 */
	protected static function extractFromRequest(int|array|string $sources, ServerRequestInterface $request, mixed $default = null): mixed
	{
        //\Saf\Debug::outData(['extracting', $sources]);
        //#TODO merge back into extractParam?
		$result = $default;
		if (!is_array($sources)) {
			$sources = is_int($sources) ? ['stack' => $sources] : ['request' => $sources];
		}
        \Saf\Debug::outData(['extract', 's' => $sources, 'r' => $request, 'd' => $default]);
		foreach($sources as $source => $index) {
			if (is_int($source)) {
				$source = is_int($index) ? 'stack' : 'request';
			}
            
            \Saf\Debug::outData(['source', $source]);
			switch ($source) {
				case 'stack' :
					$stack = self::getResourceStack($request);
                    \Saf\Debug::outData(['stack', 'i' => $index, 'o' => $stack]);
					if (key_exists($index, $stack) && '' !== $stack[$index] ) {
						return $stack[$index];
					}
					break;
                case 'attribute' :
                    $result = self::requestSearch($request, "A:{$index}");
                    break;
				case 'post' :
                    $result = self::requestSearch($request, "P:{$index}");
					break;
				case 'get' :
                    $result = self::requestSearch($request, "G:{$index}");
					break;
				// case 'session' : //#TODO #1.1.0 deep thought on if this should be allowed 
				// 	if (isset($_SESSION) && is_array($_SESSION) && array_key_exists($index, $_SESSION)) {
				// 		return $_SESSION[$index];
				// 	}
				// 	break;
				case 'request' :
                    $result = self::requestSearch($request, self::defaultRequestSearchOrder().":{$index}");
                    
                    \Saf\Debug::outData(['request', 'i' => $index, gettype($request), 'r' => $request, $result]);
                    break;
					// if ($request->has($index)) {
					// 	return $request->getParam($index);
					// }
                default:
                    $result = self::requestSearch($request, "{$source}:{$index}");
			}
            if (!is_null($result)) {
                return $result;
            }
		}
		return $default;
	}

    public static function requestSearch(ServerRequestInterface $request, string $search): mixed
    {
        \Saf\Debug::outData(['search', $search]);
        $sourceParts = explode(':', $search, 2);
        $source = count($sourceParts) ? $sourceParts[0] : StandardRequestHandler::DEFAULT_REQUEST_SEARCH;
        $index = count($sourceParts) ? $sourceParts[1] : $sourceParts[0];
        $order = array_unique(str_split($source));
        foreach($order as $facet) {
            switch ($facet) {
                case 'A':
                    $attribute = $request->getAttribute($index, null);
                    if (!is_null($attribute)) {
                        return $attribute;
                    }
                    break;
                case 'P':
                    $post = $request->getParsedBody();
                    if (is_array($post) && key_exists($index, $post)) {
                        return $post[$index];
                    } // #TODO parsedBody can also be an object?
                    break;
                case 'G':
                    $get = $request->getQueryParams();
                    if (key_exists($index, $get)) {
                        return $get[$index];
                    }
                    break;
                default:
                    //\Saf\Debug::outData(["unsupported search: {$facet} on {$index}"]);
            }
        }
        return null;
    }

    /**
     * determine what processor function is being requested in a resourceStack Uri
     */
    public static function detectFunction(ServerRequestInterface $request, ?string $field = StandardRequestHandler::STACK_ATTRIBUTE): ?string
    {
        $stack = self::getResourceStack($request, $field) ?: [];
        return array_shift($stack);
    }

    public static function peekResource($request)
    {
        $resourceStack = self::getResourceStack($request);
        return array_shift($resourceStack);
    }

    /**
     * returns a __copy__ of the resource stack (array list of Uri parts)
     * resource stack should always be stored as a string, but if for some reason it wasn't, return it (as is)
     */
    public static function getResourceStack(ServerRequestInterface $request, ?string $field = StandardRequestHandler::STACK_ATTRIBUTE): array
    {
        $value = $request->getAttribute($field ?: self::getResourceStackAttribute());
        return is_string($value) ? self::parseResourceUri($value) : ($value ?: []);
    }

    /**
     * get an updated $request with new resource stack
     */
    protected static function updateRequestStack(ServerRequestInterface $request, string|array $resourceStack, ?string $field = StandardRequestHandler::STACK_ATTRIBUTE) : ServerRequestInterface
    {
        return $request->withAttribute($field, is_string($resourceStack) ? $resourceStack : implode('/', $resourceStack));
    }

    /**
     * get (take) the first element from the resource stack
     */
    public static function shiftResource(ServerRequestInterface $request): ?string
    {
        $resourceStack = self::getResourceStack($request);
        $first = array_shift($resourceStack);
        $request = self::updateRequestStack($request, $resourceStack);
        return $first; //#TODO verify this handles the object/reference as intended
    }

    /**
     * returns the attribute name for resource stack storage/retrieval
     */
    public static function getResourceStackAttribute(): string
    {
        return StandardRequestHandler::STACK_ATTRIBUTE;
    }

    /**
     * splits a resourceUri into a data structure
     */
    public static function parseResourceUri(?string $uri = ''): array
    {
        return explode(StandardRequestHandler::URI_PATH_DELIM, trim($uri));
    } 

}
