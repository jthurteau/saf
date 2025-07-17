<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Class for managing access
 */

namespace Saf\Auth;

class Access
{

    public const string KEY_OPEN = 'open';
    public const string KEY_ANY_USER = 'any-user';
    public const string KEY_KEY = 'key';
    public const string KEY_USER = 'user';
    public const string KEY_ROLE = 'role';
    public const string TYPE_OPEN = 'open-access';
    public const string TYPE_AUTH = 'authorized-access';
    public const string TYPE_KEY = 'key-access';
    public const string TYPE_ROLE = 'role-access';
    public const string TYPE_LOGIN = 'login-required';
    public const string TYPE_LOCK = 'key-required';
    public const string TYPE_DENIED = 'access-required';
    public const string TYPE_NONE = 'no-access';
    
    public static function is(string $criteria, string $key): bool
    {
        return str_ends_with($criteria, "-{$key}");
    }
}