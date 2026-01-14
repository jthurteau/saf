<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Front trait to allow Resources access to protected Front methods
 */

namespace Saf\Module;

// use Saf\Module\Union; // in namespace

trait UnionFront {

    public const string FRONT_CONFIG_INDEX = Union::FRONT_CONFIG_INDEX;

    protected static ?object $singleton = null;

    /**
     * returns an array granting proxy access to mix in with config arrays. 
     */
    protected function frontProxyConfig(): array
    {
        return [self::FRONT_CONFIG_INDEX => $this->getProxy()];
    }

    /**
     * returns a callable allowing privilidged access to protected methods.
     * as a protected function, it must be called by the trait implementer 
     * and the result passed to an object to be blessed.
     */
    protected function getProxy(): object
    {
        !self::$singleton && (self::$singleton = function(null|array|string $request = null): mixed {
            return is_null($request) ? $this : $this->proxy($request);
        });
        return self::$singleton;
    }

    /**
     * proxies a request to privilidged callers blessed via getProxy().
     * accepts a string to call a protected method of that name with no paramters,
     * or an array with the first element being a string (method name) and 
     * the second being an array of parameters to pass to the method.
     */
    protected function proxy(array|string $request): mixed
    {
        $method = is_array($request) ? array_shift($request) : $request;
        $params = is_array($request) ? array_shift($request) : null;
        if($method && is_string($method) && method_exists($this, $method)) {
            return is_array($params) ? $this->$method(...$params) : $this->$method();
        }
    }

}