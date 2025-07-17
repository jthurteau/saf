<?php

/**
 * init tether, binds a canister to core callables
 * 
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link saf.src:kickstart/init.php7.tether.php
 * @link install:kickstart/init.tether.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return function &(array|ArrayAccess &$canister = []) : array|ArrayAccess {
    static $init = null; #NOTE static closure vars only get assigned once.
    if ($init) {
        return $canister;
    }
    $aim = 'Agent installer misconfigured, ';
    if (!is_array($canister) && !($canister instanceof ArrayAccess)) {
        throw new Exception("{$aim}Canister Invalid", 126);
    }
    if (
        !key_exists('installPath', $canister)
    ) {
        throw new Exception("{$aim}Canister has no installPath", 126);
    }
    if (
        !is_string($canister['installPath'])
        || !$canister['installPath']
    ) {
        throw new Exception("{$aim}Canister installPath invalid", 126);
    }
    key_exists('installed', $canister) || ($canister['installed'] = []);
    $registry = [];

    /**
     * noop callable
     */
    $registry['_n'] = function (): void {

    };

    /**
     * returns a callable: 
     * $lookup if is is callable,
     * a callable in the canister named $lookup, otherwise:
     * the noop callable
     */
    $registry['_c'] = function ($lookup) use (&$canister): mixed {
        //#TODO should _c return internal callables? (_*)
        return 
            is_callable($lookup)
            ? $lookup
            : (
                key_exists($lookup, $canister) 
                    && is_callable($canister[$lookup]) 
                ? $canister[$lookup]
                : $canister['_n']
            );
    };

    /**
     * returns values (single or iterable) as iterable
     */
    $registry['_a'] = function ($v): array|Traversable {
        return is_array($v) || ($v instanceof Traversable) ? $v : [$v];
    };

    /**
     * installs $util as a callable
     */
    $registry['_i'] = function ($util) use (&$canister): void {
        $installablePath = 'src/kickstart/installable';
        $file = "{$canister['installPath']}/{$installablePath}/{$util}.php";
        if(!is_readable($file)) {
            $message = "Agent installer:{$util} missing.";
            throw new Exception($message, 127, new Exception($file));
        } else {
            $result = 
                is_string($file) 
                ? (
                    key_exists('validate', $canister) 
                        && is_callable($canister['validate'])
                    ? $canister['validate']($file) 
                    : require($file)
                ) : null; #TODO #2.0.0 add support for callable installers
            if (is_callable($result)) {
                $canister[$util] = $result;
                key_exists($util, $canister['installed']) 
                    || ($canister['installed'][$util] = $file);
            } else {
                $message = "Agent installer:{$util} missing.";
                throw new Exception($message, 127, new Exception($file));
            }
        }
    };

    /**
     * generates a filepath based on $name and $type
     */
    $registry['_f'] = function (string $name, string $type = '') use (&$canister) {
        $bridge = 
            strpos($name, '/src') === (strlen($name) - 4 )
            ? '/'
            : (strpos($type, '.') === 0 ? '.' : ''); //#TODO this looks wrong, strlen($type)?
        if (strpos($type, '.') === 0) {
            $type = substr($type, 1);
        }
        $basePath =
            strpos($name, '/') === 0
            ? $name
            : "{$canister['installPath']}/{$name}";
            //#TODO if the name specifies .php, truncate $type to bypass typed file lookup
        $ext = 
            strrpos($name,'.php') === (strlen($name) - 4)
            ? ''
            : '.php';
        //print_r([__FILE__,__LINE__,"{$basePath}{$bridge}{$type}{$ext}",$basePath,$bridge,$type,$ext]);
        return "{$basePath}{$bridge}{$type}{$ext}";
    };

    /**
     * fatal error trigger labeled by $name, throws an exception if $fail
     * optionaly, $blame (accepts a string (e.g. file name) or Throwable)
     * if no params are passed, a list of previously triggered errors is returned.
     */
    $registry['_e'] = function(?string $name = null, null|Throwable|string $blame = null, ?string $fail = null): mixed {
        static $history = []; #NOTE static closure vars only get assigned once.
        if (is_null($name)) {
            return $history;
        }
        !key_exists($name, $history) && ($history[$name] = []);
        $blameString = is_a($blame, 'Throwable') ? $blame->getMessage() : $blame;
        $origin = is_a($blame, 'Throwable') ? $blame->getPrevious() : null;
        $historyString =
            $fail ? $fail : (string)$blameString;
        if (!is_null($fail)) {
            $failMessage = str_replace('{$}', $name, $fail);
            $fileException = $blame ? ( is_object($blame) ? $blame : new Exception($blame)) : null;
            $record = ['failure' => $failMessage];
            $blame && ($record['blame'] = $blameString);
            $origin && ($record['origin'] = [$origin->getMessage(), $origin->getTraceAsString()]);
            $history[$name][] = $record;
            throw new Exception($failMessage, 127, $fileException);
        } else {
            $record = [];
            $blame && ($record['blame'] = $blameString);
            $origin && ($record['origin'] = [$origin->getMessage(), $origin->getTraceAsString()]);
            $history[$name][] = $record;
            return null;
        }
    };

    /**
     * default deep merge logic, preserve named keys and union numerically keyed values
     */
    $registry['_m'] = function(int|string|array|ArrayAccess $original, $new) use (&$canister): int|string|array|ArrayAccess {
        if (
            is_array($original) || ($original instanceof ArrayAccess)
            || is_array($new) || ($new instanceof ArrayAccess)
        ) {
            $original = $canister['_a']($original);
            foreach($canister['_a']($new) as $newKey => $newValue) {
                $naturalKey = preg_match('/^(0|([1-9][0-9]?))$/', $newKey);
                if (!key_exists($newKey, $original)) {
                    $original[$newKey] = $newValue;
                } elseif (!$naturalKey) {
                    $original[$newKey] = $canister['_m']($original[$newKey], $newValue);
                } else {
                    $original[] = $newValue;
                }
            } 
        }
        return $original;
    };

    /**
     * summarize potentially large trees of data
     */
    $registry['_s'] = function($original, $depth = null) use (&$canister): string {
        if (is_null($depth) || is_nan($depth)) {
            $depth = key_exists('maxSummaryDepth', $canister) ? $canister['maxSummaryDepth'] : 10;
        }
        if (
            is_array($original) || is_object($original)
        ) {
            $return = gettype($original) . '( ';
            if (is_array($original)) {
                $first = true;
                foreach($original as $key => $value) {
                    $return .= (
                        ($first ? '' : ', ') 
                        . "[{$key}] => "
                        . (
                            $depth > 1 
                            ? $canister['_s']($value, $depth - 1) 
                            : ('[' . gettype($value) . ']' )
                        )
                    );
                    $first = false;
                }
            } else {
                $objectSummary = ': ';
                if (is_a($original, 'Error') || is_a($original, 'Exception')) {
                    $objectSummary .= (
                        $original->getMessage() 
                        . ': \n' . $original->getTraceAsString()
                        . (
                            $original->getPrevious() 
                            ? (
                                $depth > 1
                                ? $canister['_s']($original->getPrevious(), $depth - 1)
                                : ': has previous error/exeception beyond depth'
                            ) : ''
                        )
                    );
                }
                return $return .= (get_class($original) . $objectSummary . ')'); //#TODO more
            }

            return $return . ')';
        } elseif (is_string($original)) {
            $original = htmlentities($original);
        }
        return print_r($original, true);
    };

    /**
     * handler for failed venting (different pattern than other file types)
     * #TODO only do this in dev, replace default production behavior with something pluggable and more appropriate
     */
    $registry['_v'] = function($name, $file = null, $fail = 'Failed to load vent: {$}'): void{
        $failMessage = str_replace('{$}', $name, $fail);
?>
    <div class="safVentError">
        <span class="location"><?php print(__FILE__. '  :' . __LINE__); ?></span> 
        <span class="message"><?php print($failMessage); ?></span>
    </div>
<?php
};
    /**
     * installs one or more installables in $utilList
     */
    $registry['install'] = function($utilList) use (&$canister): void {
        foreach ($canister['_a']($utilList) as $uIndex => $u) {
            if (!is_string($u)) {
                $message = "Agent installer: [{$uIndex}] invalid name.";
                throw new Exception($message, 127);
            }
            $canister['_i']($u);
        }
    };

    /**
     * uninstalls a callable
     */
    $registry['uninstall'] = function () use (&$canister){
        foreach ($canister as $key => $value) {
            if (is_callable($canister[$key])) {
                unset($canister[$key]);
                if (key_exists($key, $canister['installed'])) {
                    unset($canister['installed'][$key]);
                }
            }
        }
    };

    /**
     * returns a new canister with references to every value, but no callables
     * this allows seralization and safer introspection
     */
    $registry['shell'] = function &($redact = true) use (&$canister): array|ArrayAccess {
        $shell = [];
        $installed = key_exists('installed', $canister) ? $canister['installed'] : [];
        foreach ($canister as $key => $value) {
            if ('installed' == $key) {
                $shell['previouslyInstalled'] = $canister[$key];
            } elseif (!is_callable($canister[$key])) {
                $shell[$key] = &$canister[$key];
            } else {
                $v = new ReflectionFunction($canister[$key]);
                $used = $v->getClosureUsedVariables();
                if (!in_array($canister, $used) && $v->getFileName() != __FILE__) {
                    $shell[$key] = &$canister[$key];
            }
        }
        } 
        if ($redact) {
            $shell = $canister['redact']($shell);
        }
        return $shell;
    };

    /**
     * redact potentially sensitive information
     */
    $registry['redact'] = function($sensitive = null) use (&$canister): array|ArrayAccess { //#TODO set $senstive to string or callable
        static $list = []; #NOTE static closure vars only get assigned once.
        if (is_array($sensitive)) {
            $redacted = [];
            //#TODO merge in live redaction data from canister
            foreach($sensitive as $key => $value) {
                if (in_array($key, $list) || in_array($value, $list)) {
                    $redacted[$key] = '**redacted**';
                } else {
                    $redacted[$key] = $value;
                }
            }
            //#TODO also support callables to prune: foreach($list ... if is_callable... redacted = $callable($sensitive))
            return $redacted;
        } else {
            $list[] = $sensitive;
            return $list;
        }
    };

    /**
     * apply the callable $method to each value in $options until:
     * one returns truthy, return that callable's result
     */
    $registry['first'] = function ($options, $method) use (&$canister): mixed {
        $callable = $canister['_c']($method);
        if (is_null($callable)) {
            return null;
        }
        foreach($canister['_a']($options) as $option) {
            $result = $callable(...$canister['_a']($option));
            if ($result) {
                return $result;
            }
        }
    };

    /**
     * apply the callable $method to each value in $options:
     * return all results
     */
    $registry['each'] = function ($options, $method) use (&$canister): array {
        $callable = $canister['_c']($method);
        if (is_null($callable)) {
            return null;
        }
        $results = [];
        //print_r([__FILE__,__LINE__,'[each]',$options,$method]);
        foreach($canister['_a']($options) as $optionKey => $option) {
            $o = $canister['_a']($option);
            //print_r([__FILE__,__LINE__,"[each:{$optionKey}]",$option,$o]);
            $results[$optionKey] = $callable(...$o);
        }
        return $results;
    };

    /**
     * tests each $options with $method:
     * returns true when the first returns truthy
     */
    $registry['any'] = function ($options, $method = null) use (&$canister): bool {
        $callable =
            !is_null($method)
            ? $canister['_c']($method)
            : function($options) {
                return (bool)$options;
            };
        foreach($canister['_a']($options) as $optionKey => $option) {
            $result = is_callable($callable) ? $callable($option) : null;
            if ($result) {
                return $result;
            }
        }
        return false;
    };

    /**
     * tests each $options with $method:
     * returns false when the first returns falsy, true otherwise
     */
    $registry['all'] = function ($options, $method = null) use (&$canister): bool {
        foreach($canister['_a']($options) as $optionKey => $option) {
            $result = $canister['any']([$option], $method);
            if (!$result) {
                return false;
            }
        }
        return true;
    };

    /**
     * tether the canister to $tether
     */
    $registry['tether'] = function (
        string $tether, ?string $fail = null
    ) use (
        &$canister
    ): mixed {
        $tetherFile = $canister['_f']($tether, '.tether');
        $genericFile = $canister['_f']($tether);
        if(!file_exists($tetherFile) || !is_readable($tetherFile)){
            if (!file_exists($genericFile) || !is_readable($genericFile)) {
                return $canister['_e']($tether, $tetherFile, $fail);
            } else {//#TODO compare typed to generic to save fileloading overhead when duplicate
                $tether = require($genericFile);
            }
        } else {
            $tether = require($tetherFile);
        } #TODO $file = $canister['_v']($name, $type, $fail); //validate
        return is_callable($tether) ? $tether($canister) : $tether;
    };

    /**
     * load root $root
     */
    $registry['root'] = function(string $root, ?string $fail = null) use (&$canister): mixed {
        //#TODO
        if (strpos($root,':') !== false) {
            return null; //#TODO parse and unserialize
        }
        //print_r([__FILE__,__LINE__,'[root]',$root,$fail]);
        try {
        $rootFile = $canister['_f']($root, '.root');
        $genericFile = $canister['_f']($root);
        if(!file_exists($rootFile) || !is_readable($rootFile)){
            if (!file_exists($genericFile) || !is_readable($genericFile)) {
                return $canister['_e']($root, $rootFile, $fail);
            } else {//#TODO compare typed to generic to save fileloading overhead when duplicate
                $root = require($genericFile);
            }
        } else {
            $root = require($rootFile); 
        } #TODO $file = $canister['_v']($name, $type, $fail); //validate
        } catch (\Error | \Exception $e) {
            $rootException = new Exception($rootFile, 0, $e);
            return $canister['_e']($root, $rootException, $fail);
        }
        //#TODO store invalid root data in canister?
        //print_r([__FILE__,__LINE__,'[root:result]',$root]);
        return 
            is_array($root) || $root instanceof ArrayAccess 
            ? $root 
            : [];
    };

    /**
     * vent data in $final (along with the $canister) to $vent
     */
    $registry['vent'] = function ($final, $vent = null) use (&$canister): mixed { //#TODO ?string or callable
        $defaultPayload = ['result' => $final, 'ventFile' => __FILE__];
        $errors = error_get_last();
        $allowLeak = (
            key_exists('localDevEnabled', $canister) && $canister['localDevEnabled']
            && (
                (key_exists('enableDebug', $canister) && $canister['enableDebug'])
                || (key_exists('forceDebug', $canister) && $canister['forceDebug'])
            )
        );
        if ($errors) {
            $defaultPayload['errors'] = $errors;
        }
        if ($allowLeak) {
            $defaultPayload['debugCanister'] = $canister['shell']();
        }
        if ($vent) {
            if (is_string($vent)) {
                $ventFile = $canister['_f']($vent, '.vent');
                $genericFile = $canister['_f']($vent);
                if(!file_exists($ventFile) || !is_readable($ventFile)){
                    if (!file_exists($genericFile) || !is_readable($genericFile)) {
                        return $canister['_e']($vent, $ventFile); //#TODO this pattern may not work for vent
                    } else {//#TODO compare typed to generic to save fileloading overhead when duplicate
                        $vent = require($genericFile);
                    }
                } else {
                    $vent = require($ventFile); 
                }
            }
            #TODO handle auditing for failed vents
            return is_callable($vent) ? $vent($final, $canister) : $vent;
        } else {
            $defaultPayload['result'] = $canister['_s']($defaultPayload['result']);
            $defaultPayload['debugCanister'] = $canister['_s']($defaultPayload['debugCanister']);
            print_r($defaultPayload);
            return true;
        }
    };

    //# TODO $registry['sema'] = function

    /**
     * store executable state with quay 
     */
    $registry['quay'] = function(mixed $data, string $quay, ?string $fail = null) use (&$canister): mixed {
        $quayFile = $canister['_f']($quay, '.quay');
        $genericFile = $canister['_f']($quay);
        if(!file_exists($quayFile) || !is_readable($quayFile)){
            if (!file_exists($genericFile) || !is_readable($genericFile)) {
                return $canister['_e']($quay, $quayFile, $fail);
            } else {
                $quay = require($genericFile);
            }
        } else {
            $quay = require($quayFile);
        } #TODO $file = $canister['_v']($name, $type, $fail); //validate
        return is_callable($quay) ? $quay($data, $canister) : $quay;
    };

    /**
     * pass executable state to store with inlet 
     */
    $registry['inlet'] = function(mixed $data, string $inlet, ?string $fail = null) use (&$canister) {
        $inletFile = $canister['_f']($inlet, '.inlet');
        $genericFile = $canister['_f']($inlet);
        if(!file_exists($inletFile) || !is_readable($inletFile)){
            if (!file_exists($genericFile) || !is_readable($genericFile)) {
                return $canister['_e']($inlet, $inletFile, $fail);
            } else {
                $inlet = require($genericFile);
            }
        } else {
            $inlet = require($inletFile);
        } #TODO $file = $canister['_v']($name, $type, $fail); //validate
        return is_callable($inlet) ? $inlet($data, $canister) : $inlet;
    };

    /**
     * merge values in $root to the canister, preserves values for existing keys
     */
    $registry['merge'] = function($root, ?string $fail = null) use (&$canister): void { #TODO string|array|ArrayAccess
        //print_r([__FILE__,__LINE__,'[merge]',$root,$fail]);
        if (!is_array($root) && !($root instanceof ArrayAccess)) {
            $root = $canister['root']($root, $fail);
        }
        foreach($root ?: [] as $rootKey => $rootValue) {
            key_exists($rootKey, $canister) || ($canister[$rootKey] = $rootValue);
            //#TODO if is_callable, rebind to $canister
        }
    };

    /**
     * overwrite values in $root to the canister
     */
    $registry['replace'] = function($root, ?string $fail = null) use (&$canister): void { #TODO string|array|ArrayAccess
        if (!is_array($root) && !($root instanceof ArrayAccess)) {
            $root = $canister['root']($root, $fail);
        }
        foreach($root as $rootKey => $rootValue) {
            $canister[$rootKey] = $rootValue;
            //#TODO if is_callable, rebind to $canister
        }
    };

    /**
     * deep merge values in $root to the canister using $method (defaults to _m)
     */
    $registry['deep'] = function(
        $root, ?string $fail = null, ?callable $method = null
    ) use (
        &$canister
    ): void {
        if (!is_array($root) && !($root instanceof ArrayAccess)) {
            $root = $canister['root']($root, $fail);
            if (!is_array($root)) {
                return;
            }
        }
        $method = 
            is_callable($method)
            ? $method 
            : $canister['_m'];
        foreach($root as $rootKey => $rootValue) {
            $canister[$rootKey] = 
                !key_exists($rootKey, $canister)
                ? $rootValue
                : $method($canister[$rootKey], $rootValue);
            //#TODO if is_callable, rebind to $canister
        }
    };

    foreach($registry as $key => $callable) {
        $canister['installed'][$key] = __FILE__ . ":{$key}";
        $canister[$key] = $callable;
    }
    key_exists('requires', $canister) && $canister['install']($canister['requires']);
    $init = true;
    return $canister;
};