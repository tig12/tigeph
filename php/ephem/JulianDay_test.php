<?php
/******************************************************************************
    Test Meeus1 usage
    
    @license    GPL
    @history    2021-02-03 01:46:24+01:00, Thierry Graff : Creation
********************************************************************************/

// require tigeph autoload
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'autoload.php';

use tigeph\ephem\JulianDay;

$jd = JulianDay::isoDate2jd('2000-01-01 12:00:00');
echo "JD = $jd\n"; // 2451545
