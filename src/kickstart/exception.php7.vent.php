<?php 
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * default exception/error vent, accepts an optional canister to use in a view script
 */

declare(strict_types=1);

return function (
    array &$canister = []
){
    if (!file_exists(__DIR__.'/views/gateway.php') || !is_readable(__DIR__.'/views/gateway.php')) {
        #TODO #2.0.0 meditate on missing view
        return false;
    }
    require_once(__DIR__ .'/views/gateway.php');
    return true;
};