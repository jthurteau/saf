<?php

/**
 * Constant inlet
 * 
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link saf.src:kickstart/const.php8.inlet.php
 * @link install:kickstart/const.inlet.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

/**
 * returns a callable:
 * iterates over each key/value pair in $data and sets a constant $key with $value if not already defined
 */
return function (array|ArrayAccess $data, null|array|ArrayAccess $canister = null): void {
    if(is_array($data) || ($data instanceof ArrayAccess)) {
        foreach($data as $const => $value) {
            defined((string)$const) || define((string)$const, $value);
        }
    }
};