<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * auto-pipe script, anchors $appHandle to the request url
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

return function ( #TODO #PHP8 allows throw as an expression
    array &$canister = []
){
    if (key_exists('uriMirror', $canister)) {
        $mirror = $canister['uriMirror'];
    } else { //#DISCLAIMER reading environment in a tether is not best practice.
        $mirrorSource = //#NOTE if you use host.root.php uriMirror is already set.
            key_exists('uriMirrorSource', $canister) 
            ? $canister['uriMirrorSource']
            : 'PHP_SELF'; //#NOTE sometimes also available in 'SCRIPT_NAME',
        $mirror = $_SERVER[$mirrorSource];
    }
    
    //#NOTE resolverPylon is set in host.root.php from $_SERVER['PHP_RESOLVER_PYLON'] when present
    //#NOTE you may alternatively set Saf\RESOLVER_PYLON in your pylon
    if (!key_exists('resolverPylon', $canister) && defined('Saf\RESOLVER_PYLON')) {
         $canister['resolverPylon'] = Saf\RESOLVER_PYLON;
    }
    $blade = 
        key_exists('resolverPylon', $canister) 
        ? strtolower("{$canister['resolverPylon']}.php")
        :  'index.php';
    $index = strpos($mirror, $blade);
    $length = ($index !== false ? $index : PHP_MAXPATHLEN);

    key_exists('baseUri', $canister) || ($canister['baseUri'] = substr($mirror, 0, $length));

    if (!key_exists('resolverRest', $canister)) {
        $request = $_SERVER['REQUEST_URI'];
        $rest = 
            key_exists('resolverPylon', $canister)
            ? substr($request, $index + strlen($canister['resolverPylon']) + 1)
            : substr($request, $index + 1);
        is_string($rest) && (strlen($rest) > 0) && ($canister['resolverRest'] = $rest);
    }

    //#NOTE resolverPylon is set by $_SERVER['PHP_RESOLVER_FORWARD'] in host.root.php
    //#NOTE you may alternatively set Saf\RESOLVER_FORWARD in your pylon
    if (!key_exists('resolverForward', $canister) && defined('Saf\RESOLVER_FORWARD')) {
        $canister['resolverForward'] = Saf\RESOLVER_FORWARD;
    }
    $canister['pipes'] = [
        'pylons' => key_exists('resolverPylon', $canister) ? [$canister['resolverPylon']] : [], #TODO this wouldn't work with caching in place...
    ];
    if (strlen($canister['baseUri']) > 1) {
        $host = null; #TODO detect if baseUri is not root relative and handle;
        $protocol = null; #TODO detect if baseUri is protocol relative;
        $path = substr($canister['baseUri'], 1, strlen($canister['baseUri']) - 2);
        $canister['pipes']['main'] = $path;
    }
    return;
};