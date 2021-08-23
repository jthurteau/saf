<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * installable closure to add root replace tool,
 * for use with $canister['install']
 * @link saf.src:kickstart/installable/replace.php
 */

declare(strict_types=1);

if (!isset($canister)) {
    throw new Exception("Calling install:" . basename(__FILE__, '.php') . " out of context.");
}
return function(string $root, ?string $fail = null) use (&$canister) {
    if (!is_array($root) && !($root instanceof ArrayAccess)) {
        $root = $canister['root']($root, $fail);
    }
    foreach($root as $rootKey => $rootValue) {
        $canister[$rootKey] = $rootValue;
        //#TODO if is_callable, rebind to $canister
    }
};