<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Trait for PSR RequestHandler implementations
 */

namespace Saf\Psr;

use Psr\Http\Message\ServerRequestInterface;
use Saf\Psr\StandardRequestHandler;
use Saf\Psr\Request\Parser\Common as Parser;
use Saf\Keys;
use Saf\Auto;
use Saf\Util\UrlRewrite;
use Saf\Util\Time;
use Saf\Auth\Access;

trait RequestHandlerCommon {
    use Parser;

    public abstract static function defaultRequestSearchOrder(): string;

    public abstract static function stackAttributeField(): string;

    protected function allowed($resource, $user = null)
    {
        //#TODO patch in with configured routes
        //$accessList = Hash::deepMerge($this->accessList,$globalAccess);
        $keys = $user ? $user->getDetail('keys') : [];
        $roles = $user ? $user->getRoles() : [];
        if ($this->matchRoute($resource, Access::KEY_OPEN)) {
            return Access::TYPE_OPEN;
        }
        if (
            $this->matchRoute($resource, Access::KEY_ANY_USER)
        ) {
            if ($user?->getIdentity()) {
                return Access::TYPE_AUTH;
            }
        }
        $userName = $user?->getIdentity() ?: 'none';
        if ($user && $this->matchRoute($resource, Access::KEY_USER . "-{$userName}")) {
            return Access::TYPE_AUTH;
        }
        $validKeys = Keys::validKeys($keys);
        if (
            count($validKeys) > 0
            && $this->matchRoute($resource, Access::KEY_KEY)
        ) {
            return Access::TYPE_KEY;
        }
        foreach($validKeys as $keyName=> $key) {
            if ($this->matchRoute($resource, $keyName)) {
                $serviceName = substr($keyName, 4);
                return "{$serviceName}-" . Access::TYPE_KEY;
            }
        }
        foreach($roles as $role) {
            $roleName = "{$role}-" . Access::KEY_ROLE;
            if ($this->matchRoute($resource, $roleName)) {
                return "{$roleName}-" . Access::TYPE_ROLE;
            }
        }
        return false;
    }

    /**
     * @param $resource
     * @param $user
     * @return string
     * deprecate in favor of accessOptions
     */
    protected function accessRecommendation($resource, $user = null): ?string
    {
        return array_shift($this->accessOptions($resource, $user));
    }

    protected function accessOptions($resource, $user = null): array
    {
        $recommendation = [];
        //#TODO patch in with configed routes
        //$accessList = Hash::deepMerge($this->accessList,$globalAccess);
        $list = $this->accessList;
        $keys = $user ? $user->getDetail('keys') : [];
        $roles = $user ? $user->getRoles() : [];
        if ($this->matchRoute($resource, 'open')) {
            return [Access::TYPE_OPEN];
        }
        if ($this->matchRoute($resource, Access::KEY_ANY_USER)) {
            if ($user->getIdentity()) {
                return [Access::TYPE_AUTH];
            }
            $recommendation[] = Access::TYPE_LOGIN;
        }
        $anyKeyAccess = $this->matchRoute($resource, Access::KEY_KEY);
        if (count($keys) > 0 && $anyKeyAccess) {
            return [Access::TYPE_KEY];
        } elseif ($anyKeyAccess) {
            $recommendation[] = Access::TYPE_LOCK;
        }
        foreach($list as $criteria => $toss){
            $isKeyAccess = 
                Access::is($criteria, Access::KEY_KEY)
                && $this->matchRoute($resource, $criteria);
            $isUserAccess =
                Access::is($criteria, Access::KEY_USER)
                && $this->matchRoute($resource, $criteria);
            $isRoleAccess =
                Access::is($criteria,Access::KEY_ROLE)
                && $this->matchRoute($resource, $criteria);

            $isKeyAccess && !in_array(Access::TYPE_LOCK, $recommendation)
                && ($recommendation[] = Access::TYPE_LOCK);
            ($isUserAccess || $isRoleAccess) && !in_array(Access::TYPE_LOGIN, $recommendation)
                && ($recommendation[] = ($user->getIdentity() ? Access::TYPE_DENIED : Access::TYPE_LOGIN));
            //#TODO add an optional scoping param to allowed and return X-user-access, X-role-access, X-key-access
        }
        return $recommendation;
    }

    protected static function resourceMap(string $handlerNamespace, $resource)
    {
        $namespaceStack = explode('\\', $handlerNamespace);
        foreach ($resource as $index=>$part) {
            $resource[$index] = ucfirst($part);
        }
        if (count($namespaceStack) > 1 && $namespaceStack[count($namespaceStack) - 1] == 'Handler') {
            $resourceNamespaceStack = $namespaceStack;
            $resourceNamespaceStack[count($resourceNamespaceStack) - 1] = 'Resource';
            return array_merge($resourceNamespaceStack, $resource);
        }
        return array_merge($namespaceStack, $resource);
    }

    protected function route($resource, ServerRequestInterface $request)
    {
        if (count($resource) < 3) {
            return [
                'success' => false,
                'request' => $resource,
                'message' => 'resource routing underflow'
            ];
        }
        $base = implode('\\', array_slice($resource, 0, 3));
        $rest = array_slice($resource, 3);
        if (class_exists($base) && method_exists($base, 'handle')) {
            if (key_exists($base, $this->models)) {
                $match = $this->models[$base];
            } else {
                $match = new $base();
            }
            return $match->handle($request, $rest);
        }
        // // $match = $base; //#TODO think on this more
        // $test = $base;
        // foreach ($rest as $part) {
        //     $test = $test .= "\\{$part}";
        //     if (
        //         !Auto::validClassName($part) 
        //     ) {
        //         break;
        //     }
        //     if (
        //         class_exists($test) 
        //         && method_exists($test, 'handle')
        //     ) {
        //         $match = $test;
        //     }
        // }
        // if (
        //     $match != $base 
        //     || (class_exists($base) && method_exists($base, 'handle'))
        // ) {
        //     $match = new $base();
        //     return $match->handle($request, $rest);
        // }
        return [
            'success' => false,
            'request' => $resource,
            'message' => 'resource routing failure'
        ];
    }

    protected function matchAcl($resource, $list)
    {
        foreach($list as $resourceToken) {
            if (
                '*' == $resourceToken
                || ('' == $resource && '.' == $resourceToken)
                || $resource == $resourceToken
                || (
                    strpos($resourceToken, '*') !== false
                    && self::matchToken($resource, $resourceToken)
                )
            ) {
                return true;
            }
        }
        return false;
    }

    protected static function matchToken($string, $match)
    {
        return false; //#TODO
    }

    protected function matchRoute($resource, $in)
    {
        return
            key_exists($in, $this->accessList) 
            && $this->searchRoutes($resource, $this->accessList[$in]);
    }

    protected function searchRoutes($resource, $list)
    {
        if ($list == '*') {
            return true;
        }
        is_array($resource) || ($resource = explode('/', $resource));
        is_array($list) || ($list = [$list]);
        foreach($list as $route) {
            is_array($route) || ($route = explode('/', $route));
            if (
                $route == $resource 
                || $this->matchResource($resource, $route)
            ) {
                return true;
            }
        }
        return false;
    }

    protected function matchResource(array $resource, array $route)
    {
        foreach($resource as $part) {
            if(
                !count($route)
                || (
                    '*' != $route[0]
                    && $part != $route[0] #TODO matchPart(string $part, string $route)
                )
            ) {
                return false;
            }
            $current = array_shift($route);
            if (!count($route) && $current == '*') {
                return true;
            }
        }
        return true;
    }

    protected static function getForward(ServerRequestInterface $request)
    {
        $url = trim(self::extractFromRequest(['get' => 'forwardUrl'], $request, '')); //$request->getParam('forwardUrl'));
        $code = trim(self::extractFromRequest(['get' => 'forwardCode'], $request, '')); //trim($request->getParam('forwardCode')));   
        return $url ? UrlRewrite::decodeForward($url) : UrlRewrite::decodeForward($code);
    }

    public static function successful(array|ArrayAccess $result)
    {
        return $result && key_exists('success', $result) && $result['success'];
    }

    /**
     * add any auto-calculated results
     */
    protected function &postProcess(array|ArrayAccess &$result, ?ServerRequestInterface $request = null) : array
    {
        $params = $request?->getQueryParams() ?: [];
        $requestedDebug = 
            \Saf\Debug::isEnabled() 
            && (
                key_exists('debug', $params) 
                || key_exists('debugTransaction', $params)
            );
        $requestedProfile = 
            \Saf\Debug::isEnabled() 
            && (
                key_exists('profile', $params) 
                || key_exists('profileTransaction', $params)
            );
        if ($requestedDebug && class_exists('\Saf\Util\Debug\Handler', false)) {
            $result += ['debug' => \Saf\Util\Debug\Handler::getMemory()];
        }
        if ($requestedProfile && class_exists('\Saf\Util\Profile', false)) {
            $result += ['profile' => [
                'start' => \Saf\Util\Profile::getStartTime(),
                'points' => \Saf\Util\Profile::getTags($params['profileTransaction'] ?: null),
                'binding' => \Saf\Util\Profile::getRunTime(), // #TODO detect this on final output and add 'end'
            ]];
        }
        Time::getOffset() && ($result['safTimeOffset'] = Time::getOffset());
        Time::getOffset() && ($result['debugTime'] = Time::time());
        return $result;
    }
}