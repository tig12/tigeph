<?php
/******************************************************************************
    Test Meeus1 usage
    
    @license    GPL
    @history    2021-02-03 01:46:24+01:00, Thierry Graff : Creation
********************************************************************************/

// require tigeph autoload
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'autoload.php';

use tigeph\model\SysolC;
use tigeph\ephem\meeusmall\Meeusmall;

$date = '2000-01-01 00:00:00';
$planets = SysolC::MAIN_PLANETS;
$planets[] = SysolC::MEAN_LUNAR_NODE;

$coords = Meeusmall::ephem($date, $planets);
echo "\n"; print_r($coords); echo "\n";

/* 
Array
(
    [planets] => Array
        (
            [sun] => 279.868
            [moon] => 217.284
            [mercury] => 271.126
            [venus] => 240.972
            [mars] => 327.589
            [jupiter] => 25.243
            [saturn] => 40.415
            [uranus] => 314.776
            [neptune] => 303.182
            [pluto] => 252.544
            [mean-lunar-node] => 125.07
        )

)
*/