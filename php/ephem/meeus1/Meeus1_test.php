<?php
/******************************************************************************
    Test Meeus1 usage
    
    @license    GPL
    @history    2021-02-03 01:46:24+01:00, Thierry Graff : Creation
********************************************************************************/

// require tigeph autoload
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'autoload.php';

use tigeph\model\SysolC;
use tigeph\ephem\meeus1\Meeus1;

$date = '2000-01-01 00:00:00';
$planets = SysolC::MAIN_PLANETS;
$planets[] = SysolC::MEAN_LUNAR_NODE;

$coords = Meeus1::ephem($date, $planets);
echo "\n"; print_r($coords); echo "\n";
