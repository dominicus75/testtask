<?php

/**
 * A simple, PSR-4 style autolader
 * 
 * @copyright 2022 Domokos Endre János <domokos.endrejanos@gmail.com>
 * @license MIT License (https://opensource.org/licenses/MIT)
 * 
 */

spl_autoload_register(function ($class_name) {

    $fully_qualified_name = explode("\\", $class_name);
  
    $vendor = strtolower(array_shift($fully_qualified_name));
    $class  = array_pop($fully_qualified_name).'.php';
    $items  = count($fully_qualified_name);

    if(preg_match("/^app/i", $vendor)) {
        $file = dirname(__DIR__).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR;
        $file .= ($items == 0) ? $class : implode(DIRECTORY_SEPARATOR, $fully_qualified_name).DIRECTORY_SEPARATOR.$class;
    } else {
        $file = dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.$vendor.DIRECTORY_SEPARATOR;
        if($items == 1) {
            $file .= $fully_qualified_name[0].DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$class;
        } elseif($items > 1) {
            $file .= array_shift($fully_qualified_name).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;
            $file .= implode(DIRECTORY_SEPARATOR, $fully_qualified_name).DIRECTORY_SEPARATOR.$class;
        }
    } 

    if (file_exists($file)) { require $file; }
  
});
