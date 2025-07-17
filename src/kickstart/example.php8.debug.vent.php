<?php

/**
 * Localizer Vent (supersedes default gateway vent)
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link   saf.src:kickstart/example.php8.debug.vent.php
 * @link   install:local-dev.debug.vent.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return (static function ($result, array|\Saf\Canister|null &$canister = null) {
    $resultInspect = 
        is_array($result) 
        ? array_keys($result)
        : gettype($result);
    $canisterInspect = 
        is_array($canister) 
        ? array_keys($canister)
        : gettype($canister);
    $meditationVent = __DIR__ . '/src/views/meditation-adapter.php';
    $throwable = function($e) {
        return is_object($e) && in_array('Throwable', class_implements($e));
    };
    $error = 
        $throwable($result)
        ? $result
        : (
            is_array($result) 
                && key_exists('fatalMeditation', $result) 
                && $throwable($result['fatalMeditation'])
            ? $result['fatalMeditation']
            : null
        );
    if ($error && file_exists($meditationVent)) {
        $meditate = require($meditationVent);
        $meditate($error);
    } else {
        $message = !$error ? 'no error to meditate on' : 'meditation vent unavailable';
        print_r([__FILE__,__LINE__, $message, $resultInspect, $canisterInspect]);
    }
    return 1;
});