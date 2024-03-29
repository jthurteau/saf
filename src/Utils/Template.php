<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for generating messages (e.g. XML)
 */

namespace Saf\Utils;

use Saf\Utils\Reflector;

class Template
{
    protected $rawMessage = '';

    public function __construct($messageName, $ext='.xml')
    {
        $messagePath = \Saf\APPLICATION_PATH . "/messages/{$messageName}{$ext}";
        if(!file_exists($messagePath)){
                throw new \Exception("Template for {$messageName} not found.");
        }
        $this->rawMessage = file_get_contents($messagePath);
    }
    
    public function get($params = null)
    {
        return
            is_null($params)
            ? $this->rawMessage
            : Reflector::dereference($this->rawMessage, $params);
    }

    public static function render($phpPath, $canister){
        $output = '';
        try{
            ob_start();
            //var_export($context);
            require($phpPath);
            $output .= ob_get_clean();
            return $output;
        } catch (\Error | \Exception $e) {
            throw new \Exception("Emmiter Exception while rendering {$phpPath}", 500 , $e);
        }
    }
}

