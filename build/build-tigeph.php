<?php
/********************************************************************************
    CLI (command line interface) to work on tigeph (test, bench, build).
    
    usage : php buld-tigeph.php
    and follow error message
    
    @license    GPL
    @copyright  Thierry Graff
    @history    2021-02-05 15:06:27+01:00, Thierry Graff : Creation
********************************************************************************/

define('DS', DIRECTORY_SEPARATOR);

//require_once __DIR__ . DS . 'src' . DS . 'init' . DS . 'init.php';
require_once 'autoload.php';

//
// parameter checking
//
$commands = ['bench'];
$commands_str = implode(', ', $commands);

$USAGE = <<<USAGE
-------                                                                                               
Usage : 
    php {$argv[0]} <command> <step>
Example :
    php {$argv[0]} bench
-------

USAGE;

//
// --- $argv[1] : command ---
//
if($argc < 2){
    echo "WRONG USAGE -{$argv[0]}  needs at least 1 argument\n";
    echo $USAGE;
    exit;
}
else{
    if(!in_array($argv[1], $commands)){
        echo "INVALID COMMAND : {$argv[1]}\n";
        echo $USAGE;
        echo "Possible values for argument1 : $commands_str\n";
        exit;
    }
}
// here, $argv[1] is valid

//
// --- run ---
//
try{
    switch($argv[1]){
        case 'bench': 
            $possibleArg2 = ['precision', 'time'];
            $possibleArg2_str = implode(', ', $possibleArg2);
            if($argc < 3){
                echo "WRONG USAGE\n";
                echo "    {$argv[0]} {$argv[1]}\n";
                echo "    needs at least 2 arguments\n";
                echo "Possible values for argument2 : $possibleArg2_str\n";
                exit;
            }
            else{
                if(!in_array($argv[2], $possibleArg2)){
                    echo "INVALID ARGUMENT : {$argv[2]}\n";
                    echo "    {$argv[0]} {$argv[1]}\n";
                    echo "    needs at least 2 arguments\n";
                    echo "Possible values for argument2 : $possibleArg2_str\n";
                    exit;
                }
            }
            // here, $argv[2] is valid
            switch($argv[2]){
            	case 'precision': buildeph\bench\precision::execute(); break;
            	case 'time'     : buildeph\bench\time::execute(); break;
            }
        break;
    }
}
catch(Exception $e){
    echo 'Exception : ' . $e->getMessage() . "\n";
    echo $e->getFile() . ' - line ' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
