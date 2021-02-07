<?php
/** 
    Unique autoload code to include
    Contains PSR-4 autoload for namespace "buildeph"
    and inclusion of autoload for vendor code.
    
    @history    2021-02-05 15:07:59+01:00, Thierry Graff : Creation 
**/

// autoload for tigeph
$rootdir = __DIR__;
require_once implode(DS, [dirname($rootdir), 'php', 'autoload.php']);

/** 
    Autoload for observe namespace
**/
spl_autoload_register(
    function ($full_classname){
        $namespace = 'buildeph';
        if(strpos($full_classname, $namespace) !== 0){
            return; // not managed by this autoload
        }
        $root_dir = __DIR__; // root dir for this namespace
        $classname = str_replace($namespace . '\\', '', $full_classname);
        $classname = str_replace('\\', DS, $classname);
        $filename = $root_dir . DS . $classname . '.php';
        $ok = include_once($filename);
        if(!$ok){
            throw new \Exception("AUTOLOAD FAILS for class $full_classname");
        }
    }
);
