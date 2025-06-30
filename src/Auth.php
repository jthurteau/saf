<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for authentication
 */

namespace Saf;

use Saf\Auth\Plugin\Local;
use Psr\Http\Message\ServerRequestInterface; #TODO currently still uses bare access
use Psr\Container\ContainerInterface;
use Saf\Psr\Container;
use Saf\Utils\Filter\Truthy;
use Saf\Auto;
use Saf\Hash;
use Saf\Session;
use Saf\Util\Ground;
use Saf\Keys; //#TODO improve this integration (maybe switch to direct Plugin\Key dependency)
use Saf\Util\Layout; //#TODO clean up this integration
use Saf\Audit; //#TODO clean up this integration
use Saf\Util\UrlRewrite;
use Saf\Exception\Redirect;
#TODO split out plugin functionality

class Auth
{
    public const PLUGIN_INFO_USERNAME = 'username';
    public const PLUGIN_INFO_USERID = 'userid';
    public const PLUGIN_INFO_REALM = 'realm';
    public const PLUGIN_INFO_FIRSTNAME = 'firstName';
    public const PLUGIN_INFO_LASTNAME = 'lastName';
    public const PLUGIN_INFO_PREFNAME = 'preferredName';
    public const PLUGIN_INFO_FULLNAME = 'fullName';
    public const PLUGIN_INFO_EMAIL = 'email';
    public const REALM_FIELD = 'loginRealm';
    public const USER_AUTODETECT = null;
    public const int MODE_SIMULATED = 1;
    public const string SIMULATED_AUTH_USERS = '\\Saf\\AUTH_SIMULATED_USERS';
    public const SIMULATED_AUTH_LOCK_KEY = 'simulated_login_lock';
    public const SIMULATED_AUTH_USER_KEY = 'simulated_user';
    public const string SIMULATED_AUTH_KEY_PARAM = 'simulated_login_key';
    //const MODE_SESSIONLESS = 2; //#TODO
    //const MODE_KEYONLY = 4; //#TOD

    protected const STATUS_CANNOT_CREATE_USER = '009';

    protected static $loadedPlugins = [];
    protected static $defaultPlugins = [];
    protected static $defaultPluginName = '';
    protected static $initialized = false;
    protected static $classMap = [];
    protected static $autocreate = false;
    protected static $allowGuest = false;
    protected static $autoKey = true;
    protected static $authenticated = false;
    protected static $credentialMissmatch = false;
    protected static $activePlugin = null;
    protected static $userObject = null;
    protected static $errorMessages = [];
    protected static $loadedConfig = null;
    protected static $supportsInternal = false;
    protected static $postLoginHooks = [];
    protected static $simKeys = [];
    
    public function __invoke(ContainerInterface $container, string $name, callable $callback): Object
    {

        $authConfig = Container::getOptional($container, ['config', 'auth'], []);//Hash::extractIfArray('auth', $containerConfig, []));
        $authConfig =& Ground::ground($authConfig);
        self::init($authConfig);
        $keys = Container::getOptional($container, ['config', 'keys'], []);
        $keys =& Ground::ground($keys);
        if ($keys) {
            Keys::setServiceKeys($keys);
        }
        self::autodetect();
        self::autoKey(); //#TODO move this before sutodetect and rename initKeys?
        return $callback();
    }

    public static function init($config = [])
    {
        if (self::$initialized) {
            return;
        }
        self::$loadedConfig = $config;
        if (
            key_exists('supportsInternal', $config)
            && Truthy::filter($config['supportsInternal'])
        ) {
            self::$activePlugin = new Local();
            self::$supportsInternal = true;
            if (key_exists('simulatedAuthKeys', $config)) {
                self::$simKeys = self::parseConfigList($config['simulatedAuthKeys']);
            }
        }
        $plugins =
            key_exists('plugin', $config)
            ? (
                Hash::isNumericArray($config['plugin'])
                ? $config['plugin']
                : [$config['plugin']]
            ) : [];
        self::$autocreate = Hash::extract('autocreateUsers', $config, false);
        self::$allowGuest = Hash::extract('allowGuest', $config, false);
        $firstPass = true;
        foreach($plugins as $pluginConfig) {
            if (
                is_array($pluginConfig)
                && key_exists('name', $pluginConfig)
            ) {
                $pluginName = $pluginConfig['name'];
                $pluginConfig = $pluginConfig;
            } else {
                $pluginName = $pluginConfig ? trim($pluginConfig) : $pluginConfig;
                $pluginConfig = [];
            }
            if ($firstPass) {
                self::$defaultPlugins[] = $pluginName;
                self::$defaultPluginName = $pluginName;
                $firstPass = false;
            } else if (key_exists('default', $pluginConfig) && $pluginConfig['default']) {
                self::$defaultPlugins[] = $pluginName;
            }
            if (!$pluginName) {
                //#TODO add warning to debug
            }
            $pluginName && self::registerPlugin($pluginName, $pluginConfig);
        }
        $hooks = key_exists('postProcess', $config)
            ? (
                Hash::isNumericArray($config['postProcess'])
                ? $config['postProcess']
                : [$config['postProcess']]
            ) : [];
        foreach($hooks as $hook) {
            self::$postLoginHooks[$hook] = (
                strpos($hook,'Hook\\') !== false
                ? $hook
                : 'Hook\\' . $hook
            );
        }
        self::$initialized = true;
    }

    protected static function autokey(): void
    {
        $pluginAvailable =
            in_array('Key', self::$loadedPlugins)
            || in_array('\\Saf\\Auth\\Plugin\\Key', self::$loadedPlugins);
        $keyPluginInactive = self::$activePlugin != self::getPlugin('Key');
        self::$autoKey 
            && $pluginAvailable 
            && $keyPluginInactive 
            && self::getPlugin('Key')->auth(false);
    }

    /**
     * parses JSON like strings into an array
     */
    protected static function parseKeys(mixed $keys): array
    {

        return $keys ? (is_array($keys) ? $keys : [(string)$keys]) : [];
    }

    protected static function parseConfigList(null|string|array $list, ?string $delim = null): array
    {
        if (is_string($list)) {
            $matches = ['"' => '"','[' => ']','{' => '}'];
            $first = substr($list, 0, 1);
            $last = substr($list, -1, 1);
            if (
                in_array($first, array_keys($matches)) 
                && $last == $matches[$first]
            ) {
                $list = json_decode($list, true);
            } elseif ($delim) {
                $list = explode($delim, $list);
                foreach($list as $index => $value) {
                    $list[$index] = trim($value);
                }
            } else {
                $list = $list ? [trim($list)] : [];
            }
        }
        return $list ?? [];
    }

    public static function parseUserList(null|string|array $list, null|bool|string $limitOne = true): null|string|array
    {
        $list = self::parseConfigList($list);
        foreach($list as $index => $username) {
            $value[$index] = trim((string)$username);
            if (!$value[$index]) { 
                unset($value[$index]);
            }
        }
        if (is_string($limitOne)) {
            return in_array($limitOne, $list) ? $limitOne : null;
        }
        return $limitOne ? current($list) : $list; 
    }
    
    public static function autodetect(?int $mode = null): bool
    {
        if (!self::$initialized) {
            throw new \Exception('Attempting to authenticate before initialization.');
        }
        Session::on();
        $originalActivePlugin = self::$activePlugin;
        //throw new \Saf\Exception\Inspectable($mode,$originalActivePlugin,self::$supportsInternal,self::$activePlugin);
        if (self::$supportsInternal && self::$activePlugin) {
            $simulatedLockOn = Session::has(self::SIMULATED_AUTH_LOCK_KEY);
            $currentSimulatedUser =
                Session::has(self::SIMULATED_AUTH_USER_KEY)
                ? Hash::singleton(Session::get(self::SIMULATED_AUTH_USER_KEY))
                : '';
            //\Saf\Debug::outData(['autodetecting',$mode,$currentSimulatedUser,$simulatedLockOn, Session::get(self::SIMULATED_AUTH_USER_KEY), Hash::singleton(Session::get(self::SIMULATED_AUTH_USER_KEY)),$_SESSION]);
            if ($simulatedLockOn && is_null($mode)) {
                $mode = self::MODE_SIMULATED;
                defined(self::SIMULATED_AUTH_USERS) || define(self::SIMULATED_AUTH_USERS, [$currentSimulatedUser]);
            }
            $simUser = 
                $mode === self::MODE_SIMULATED 
                    && $currentSimulatedUser
                ? self::detectSimulatedLogin($currentSimulatedUser) 
                : null;
            if ($simUser && self::simulatedLogin($simUser)) {
                return true;
            }
        }
        $plugins = (
            !key_exists(self::REALM_FIELD, $_GET)
                || !in_array(trim($_GET[self::REALM_FIELD]),self::$loadedPlugins)
            ? self::$defaultPlugins
            : array(trim($_GET[self::REALM_FIELD]))
        );
        foreach($plugins as $pluginName){
            try {
                $plugin = self::getPlugin($pluginName);
                self::$activePlugin = $plugin;
                if($plugin->auth()) {
                    return self::login($plugin->getProvidedUsername());
                } else {
                    self::$activePlugin = null;
                }
            } catch (\Exception $e) {
                self::$activePlugin = null;
                if (Debug::isEnabled()) {
                    self::$errorMessages[] =
                    "Exception in auth plugin {$pluginName} : " . $e->getMessage();
                }
            }
        }
        if (count(self::$errorMessages) > 0) {
            count(self::$errorMessages) == 1
            ? Layout::setMessage(
                    'loginError', self::$errorMessages[0]
            ) : Layout::setMessage(
                    'loginError', 'Multiple errors: <ul><li>'
                    . implode('</li><li>', self::$errorMessages)
                    . '</li></ul>'
            );
            if (count($plugins) > 0
                    && $plugins[0] == 'Local'
                    && self::$credentialMissmatch
            ) {
                Layout::setMessage( #TODO update this
                        'passwordResetPrompt',
                        '<a href="?cmd=resetPasswordRequest">Forgotten/Lost Password</a>?'
                );
            }
        }
        if (is_null(self::$activePlugin)) {
            self::$activePlugin = $originalActivePlugin;
        }
        return false;
    }

    public static function redirect(string $url): Redirect
    {
        $redirect = new Redirect($url);
        return self::decorateRedirect($redirect);
    }

    public static function decorateRedirect(Redirect $r): Redirect
    {
        return \Saf\Debug::isVerbose() ? $r : $r->makeAutomatic();
    }

    protected static function simulatedLogin(?string $username): bool
    {
        if (
            self::login($username) && self::$activePlugin->auth()
        ){
            if (self::$authenticated) {
                Session::set(self::SIMULATED_AUTH_LOCK_KEY, true);
                Session::set(self::SIMULATED_AUTH_USER_KEY, $username);
            }
            return self::$authenticated;
        }
        return false;
    }

    public static function reauthenticate(?ServerRequestInterface $request = null): ?string
    {
        self::isExternallyLoggedIn() && self::logoutLocally();
        if ($request) {
            $simUser = self::allowedSimulatedLoginUsername($request);
            //\Saf\Debug::outData(['reauthenticating', $simUser, self::getPluginProvidedUsername()]);
            $simUser && self::login($simUser) && self::$activePlugin->auth();
        }
        return self::authenticate($request);
    }

    public static function authenticate(?ServerRequestInterface $request = null) : ?string
    {
        //\Saf\Debug::outData(['authenticating request', self::getPluginProvidedUsername()]);
        return self::getPluginProvidedUsername();
    }

    public static function isLoggedIn()
    {
        self::init();
        return
            self::isExternallyLoggedIn()
            || (
                self::$supportsInternal
                && self::isInternallyLoggedIn()
            );
    }

    public static function isExternallyLoggedIn()
    {
        self::init();
        foreach(self::$loadedPlugins as $pluginName){
            $plugin = self::getPlugin($pluginName);
            if($plugin->isLoggedIn()){
                return true;
            }
        }
        return false;
    }

    public static function isInternallyLoggedIn()
    {
        self::init();
        return key_exists('username', $_SESSION) && $_SESSION['username'];
    }

    public static function logout(string $realm = '*'): void
    {
        self::logoutLocally();
        self::logoutExternally();
    }

    public static function logoutExternally($realm = '*')
    {
        if('*' != $realm) {
            if (in_array(trim($realm),self::$loadedPlugins)) {
                $realms = [$realm];
            }
            else {
                $realms = [];
            }
        } else {
            $realms = self::$loadedPlugins;
        }
        foreach($realms as $pluginName){
            $plugin = self::getPlugin($pluginName);
            $plugin->logout();
        }
    }

    private static function getPlugin($pluginName = null)
    {
        if (is_null($pluginName) || '' == $pluginName) {
            $pluginName = self::$defaultPlugins[0];
        } #TODO handle prepending \Saf\Auth\Plugin in case the class is added fully qualified
        $pluginClass =
            array_key_exists($pluginName, self::$classMap)
            ? self::$classMap[$pluginName]
            : null;
        if ($pluginClass) {
            if (is_object($pluginClass)) {
                return $pluginClass;
            } else if (is_array($pluginClass)) {
                reset($pluginClass);
                $pluginClassName = key($pluginClass);
                $pluginConfig = current($pluginClass);
                $plugin = new $pluginClassName($pluginConfig);
            } else {
                $plugin = new $pluginClass();
            }
            return $plugin;
        } else {
            $safeName = htmlentities($pluginName);
            throw new \Exception("No such Plugin: {$safeName}");
        }
    }

    public static function getPluginName($pluginName = null)
    {
        try {
            return self::getPlugin($pluginName)->getPublicName();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"publicName\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function getExternalLoginUrl($pluginName = null)
    {
        try {
            return self::getPlugin($pluginName)->getExternalLoginUrl();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"externalLoginUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function getExternalLogoutUrl($pluginName = null)
    {
        try {
            return self::getPlugin($pluginName)->getExternalLogoutUrl();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to get property \"externalLogoutUrl\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function logoutLocally()
    {
        Session::erase(self::SIMULATED_AUTH_LOCK_KEY);
        Session::erase(self::SIMULATED_AUTH_USER_KEY);
        Session::erase('username');
        Session::clean();
    }

    public static function pluginIsLoaded($name)
    {
        self::init();
        return in_array($name, self::$loadedPlugins);
    }

    public static function getDefaultPlugin()
    {
        self::init();
        return self::$defaultPlugins[0];
    }

    public static function getDefaultPluginName()
    {
        return self::$defaultPluginName;
    }

    public static function getLoadedPlugins()
    {
        self::init();
        return self::$loadedPlugins;
    }

    public static function willAutocreateUsers()
    {
        return self::$autocreate;
    }

    public static function setStatus($success, $userObject = null, $errorCode = '')
    {
        self::$authenticated = $success;
        self::$userObject = $userObject;
        self::$activePlugin->setPluginStatus($success, $errorCode);
        if ('' != $errorCode) {
            throw new \Exception("Login Error, error code: {$errorCode}");
        }
    }

    public static function createUser($user, $userInfo)
    {
        if(!self::willAutocreateUsers()) {
            self::setStatus(false, null, self::STATUS_CANNOT_CREATE_USER);
            return false;
        }
        if(!key_exists('username', $userInfo)) {
            throw new \Exception('Must specify username to create user.');
        }
        $username = $userInfo['username'];
        $firstName = key_exists('firstName', $userInfo) ? $userInfo['firstName'] : '';
        $lastName = key_exists('lastName', $userInfo) ? $userInfo['lastName'] : '';
        $email = key_exists('email', $userInfo) ? $userInfo['email'] : '';
        return $user->createUser($username, $firstName, $lastName, $email);
        #TODO handle default attributes
    }

    public static function getPluginUserInfo($what = null)
    {
        return
			self::$activePlugin
			? self::$activePlugin->getUserInfo($what)
			: null;
    }

    public static function getPluginProvidedUsername()
    {
        return
            self::$activePlugin
            ? self::$activePlugin->getProvidedUsername()
            : '';
    }

    public static function setUsername($username)
    {
        if (self::$activePlugin) {
            self::$activePlugin->setUsername($username);
        }
    }

    public static function pluginPromptsForInfo($pluginName = NULL)
    {
        try {
            return self::getPlugin($pluginName)->promptsForInfo();
        } catch (\Exception $e) {
            $safeName = htmlentities($pluginName);
            throw new \Exception("ERROR: Attempted to check property \"promptsForInfo\" of non-existant plugin \"{$safeName}\".", $e->getCode(), $e);
        }
    }

    public static function failPlugin()
    {
        if (self::$activePlugin) {
            self::$activePlugin->fail();
        }
    }

    public static function getConfig()
    {
        return self::$loadedConfig;
    }

    public static function allowGuest()
    {
        return self::$allowGuest;
    }

    public static function detectSimulatedLogin(?string $allowed):?string
    {
        //\Saf\Debug::outData(['detecting sim login', $allowed]);
        return 
            defined(self::SIMULATED_AUTH_USERS)
                && constant(self::SIMULATED_AUTH_USERS)
            ? self::parseUserList(constant(self::SIMULATED_AUTH_USERS), $allowed)
            : self::USER_AUTODETECT;
    }

    public static function allowedSimulatedLoginUsername(ServerRequestInterface $request): false|string
    {
        $usernames = 
            defined(self::SIMULATED_AUTH_USERS)
            ? Hash::coerce(self::parseUserList(constant(self::SIMULATED_AUTH_USERS), false), Hash::MODE_AGGRESSIVE_TRUNCATE)
            : [];
        $query = $request->getQueryParams();
        $simKey = 
            self::SIMULATED_AUTH_KEY_PARAM
            && key_exists(self::SIMULATED_AUTH_KEY_PARAM, $query) 
            ? (string)$query[self::SIMULATED_AUTH_KEY_PARAM] 
            : null;
        if ($simKey) {
            foreach(self::$simKeys as $keyIndex => $key) {
                if ($key === $simKey) {
                    $possibleMatch = trim((string)$keyIndex);
                    if ($usernames && is_numeric($keyIndex)) {
                        return current($usernames);
                    } elseif (in_array($possibleMatch, $usernames)) {
                        return $possibleMatch;
                    }
                }
            }
        }
        return false;
    }

    public static function simulatedLoginValid(ServerRequestInterface $request): bool
    {
        return  self::simulatedLoginEnabled() && Auth::allowedSimulatedLoginUsername($request); 
    }

    public static function simulatedLoginEnabled(): bool
    {
        return count(self::$simKeys) > 0;
    }

    protected static function login($username = self::USER_AUTODETECT)
    {
        $wasLoggedIn = self::isInternallyLoggedIn();
        if (
            $username !== self::USER_AUTODETECT
            && '' != trim($username)
        ){
            $_SESSION['username'] = $username;
        } else if (
            $username === self::USER_AUTODETECT
            && key_exists('username', $_SESSION)
            && '' != $_SESSION['username']
        ) {
            $username = $_SESSION['username'];
        }  else {
            return false;
        }
        if (self::$activePlugin) {
            self::$activePlugin->postLogin();
        }
        if (!$wasLoggedIn && self::isInternallyLoggedIn()) {
            foreach(self::$postLoginHooks as $hookName) {
                try {
                    $hookName::trigger(['username' => $username]);
                } catch (\Exception $e) {
                    Audit::add('problem', $e->getMessage());
                }
            }
        }
        return true;
    }

    protected static function registerPlugin($pluginName, $pluginConfig = null)
    {
        if (!in_array($pluginName, self::$loadedPlugins)) {
            self::$loadedPlugins[] = $pluginName;
            $className = 'Saf\\Auth\\Plugin\\' . $pluginName;
            $internalPluginPath = __DIR__ . '/Auth/Plugin/' . Auto::classNameToPath($pluginName) . '.php';
            if (!file_exists($internalPluginPath)) {
                $className = $pluginName;
            }
            if (!class_exists($className)) {
                throw new \Exception("Failed to load configured plugin {$pluginName}");
            }
            $rootClassName = Auto::rootClass($className);
            if ($pluginConfig) {
                self::$classMap[$pluginName] = array($rootClassName => $pluginConfig);
            } else {
                self::$classMap[$pluginName] = $rootClassName;
            }
        }
    }

    public static function filterQuery(?string $query): string
    {
        $disallowed = [
            self::SIMULATED_AUTH_KEY_PARAM,
        ];
        $queryMap = Hash::fromQuery($query,'\Saf\Util\UrlRewrite::decodePair');
        foreach($disallowed as $key) {
            if (key_exists($key, $queryMap)) {
                unset($queryMap[$key]);
            }
        }
        return UrlRewrite::unmapQuery($queryMap);
    }

    public static function propAuthQuery(ServerRequestInterface $request): string
    {
        $find = [
            self::SIMULATED_AUTH_KEY_PARAM,
        ];
        $params = [];
        foreach($request->getQueryParams() as $index => $value) {
            in_array($index, $find, true) && ($params[$index] = $value);
        }
        return UrlRewrite::unmapQuery($params);
    }
}