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
    $environment = [
        //
    ];
    return [
        'environmentName' => 'dev',
        'environment' => $environment,
    ];
})();