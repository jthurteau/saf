<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authentication
 */

namespace Saf;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Hash;
use Saf\Util\Filter\Truthy;
use Saf\Auto;
use Saf\Auth\Plugin\Local;
use Saf\Audit;
use Saf\Session;
// use Saf\Environment\Define;

class Keys
{
    public const string DEFAULT_KEY_FIELD = 'key';

    protected static array $serviceKeys = [];
    protected static array $keyring = [];
    protected static string $keyField = self::DEFAULT_KEY_FIELD;

    public static function setServiceKeys($keyArray)
    {
        if (!is_array($keyArray)) {
            $keyArray = array($keyArray);
        }
        self::$serviceKeys = $keyArray;
    }

    public static function getServiceKeys()
    {
        return self::$serviceKeys;
    }

    public static function detect($includeSession = true)
    {
        return
            key_exists(self::$keyField, $_GET)
            ? $_GET[self::$keyField]
            : (
                key_exists(self::$keyField, $_POST)
                ? $_POST[self::$keyField]
                : (
                    $includeSession && Session::has(self::$keyField)
                    ? Session::get(self::$keyField)
                    : null
                )
            );
    }

    public static function storeKey($key = null, $persist = false)
    {
        if (is_null($key)) {
            $key = self::detect(false);
        }
        if ($key){
            self::$keyring[] = $key;
            if( $persist && isset($_SESSION)) {
                if (!key_exists(self::$keyField, $_SESSION)) {
                    $_SESSION[self::$keyField] = [];
                }
                if (!in_array($key, $_SESSION[self::$keyField])) {
                    $_SESSION[self::$keyField][] = $key;
                }
            }
        }
    }

    public static function getKeyring(): array
    {
        return self::$keyring;
    }

    public static function setKeyField(string $field): void
    {
        self::$keyField = $field;
    }

    public static function validKey($keyValue, $name = null): bool
    {
        $keyValue = trim((string)$keyValue);
        if (!is_null($name)) {
            return key_exists($name,self::$serviceKeys)
                && trim(self::$serviceKeys[$name]) === $keyValue;
        }
        foreach(self::$serviceKeys as $key) {
            if ($keyValue === trim($key)) {
                return true;
            }
        }
        return false;
    }

    public static function validKeys(array $keys): array
    {
        $valid = [];
        foreach($keys as $key) {
            $name = self::keyName($key);
            if ($name) {
                $valid[$name] = $key;
            }
        }
        return $valid;
    }

    public static function keyName($keyValue): ?string
    {
        foreach(self::$serviceKeys as $keyName => $key) {
            if ($keyValue === trim($key)) {
                return "key-{$keyName}";
            }
        }
        return null;
    }

}