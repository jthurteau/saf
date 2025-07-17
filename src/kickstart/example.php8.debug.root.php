<?php

/**
 * Localizer Root (injects custom local environment)
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.php8.debug.root.php
 * @link   install:local-dev.debug.root.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return (static function(){
    defined('DEBUG_START_TIME') || define('DEBUG_START_TIME', microtime(true));
    error_reporting(E_ALL);

    $app = require(__DIR__ . '/app.root.php');
    $custom = [
        # 'example' => 'foo',
    ];

    $debug = [
        'applicationHandle' => 'saf-tools', # TODO the basename(__DIR__) detection doesn't work in all local-dev deployment options
        'environmentName' => 'dev',
        'localDevEnabled' => true,
        'forceDebug' => true,
        # 'resolvableTools'=> ['log'],
        # 'applicationSuggestedPort' => '8080',
        'throwMeditations' => true,
        'psrAutoloading' => true,
        'applicationEnv' => 'local-dev',
        # 'composerVendor' => '/opt/application/vendor-for/rooms',
        # 'foundationPath' => '/opt/applicattion/vendor/Saf/src',

        # 'enableDoctor' => true, #boolean to enable/disable
        # 'snoopLog' => true, #boolean to enable/disable, or string to enable with specified log path
        # NOTE not used yet#'snoopLogPath' => '/var/www/storage/rooms/',
        ] + $custom + $app; # NOTE former overrides latter

        $localDevVent = __DIR__ . '/local-dev.debug.vent.php';
        if (file_exists($localDevVent)) {
            $debug['gatewayVent'] = function($result, &$canister = null) use ($localDevVent) {
                $localDevVent =__DIR__ . '/local-dev.debug.vent.php';
                (require $localDevVent)($result, $canister);
                return 1;
            };
        }

    $debugConstants = [
            'Saf\AUTH_SIMULATED_USERS' => 'UNITYID',
    ];
    
    key_exists('stdInlets', $debug) || $debug['stdInlets'] = []; 
    key_exists('const', $debug['stdInlets']) 
        || $debug['stdInlets']['const'] = [];
    $debug['stdInlets']['const'] = $debugConstants + $debug['stdInlets']['const'];

    $debugTools = [];//'doctor'];
    $debug['inlineTools'] =
        key_exists('inlineTools', $debug)
        ? array_unique(array_merge($debugTools, $debug['inlineTools']))
        : $debugTools;
    return $debug;
})();