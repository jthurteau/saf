<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * sample localizer pylon for SAF, defines constants to be defined by the standard pylon handler
 * #TODO LINKAGE (for now, auto linkage copy this to /config/kickstart/local-instance.root.php)
 */

declare(strict_types=1);

return (static function(){
    $constants = [
        //
        'Saf\APPLICATION_ENV' => 'production',
        'Saf\AUTH_SIMULATED_LOGIN_KEYS' => ['AUTO_GENERATE'],
        'App\DB_HOST' => 'localhost',
        'App\DB_USER' => 'ems_local',
        'App\DB_PASS' => 'AUTO_GENERATE',
        'App\DB_NAME' => 'ems_rooms_dev',
        'App\API_KEYS' => ['ONE_OR_MORE_KEYS', 'named keys' => 'can have scoped access and are tracked'],
    ];
    $environment = [
        //
    ];
    return [
        'environmentName' => 'dev',
        'environment' => $environment,
        'stdInlets' => [
            'const' => $constants,
        ],
    ];
})();