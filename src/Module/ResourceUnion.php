<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Trait for Resources that can access protected Front methods by proxy closure
 */

namespace Saf\Module;

// use Saf\Module\Union; // in namespace

trait ResourceUnion {

    /**
     * all members of the union must implement a protected getter to the
     * proxy callback.
     */
    abstract protected function frontProxy(): ?object;

    /**
     * gets the privilidged access from a provided config array
     */
    public static function proxyFront(array|\ArrayAccess $config)//: callable
    {
        $frontAccess = 
            key_exists(Union::FRONT_CONFIG_INDEX, $config) 
            ? $config[Union::FRONT_CONFIG_INDEX] 
            : null;
        return is_callable($frontAccess) ? $frontAccess : null;
    }

    /**
     * leverages privilidged access, returing the result of the requested method
     */
    protected function proxyAccess(null|array|\ArrayAccess|string $request = null): mixed
    {
        return ($this->frontProxy())($request);
    }



}