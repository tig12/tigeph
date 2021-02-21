<?php
/******************************************************************************
    Test Swetest usage
    
    See https://www.astro.com/swisseph/swetest.htm
    
    @license    GPL
    @history    2021-02-02 16:00:07+01:00, Thierry Graff : Creation
********************************************************************************/

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'autoload.php';

use tigeph\model\DomC;
use tigeph\model\SysolC;
use tigeph\ephem\swetest\Swetest;

// read config to get path to sweph binary and data files
$filename = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'config.yml';
if(!is_file($filename)){
    echo "Unable to read configuration file : $filename.\n";
    echo "Create this file and try again.\n";
    exit;
}
$config = @yaml_parse(file_get_contents($filename));
if($config === false){
    echo "Unable to read configuration file.\n";
    echo "Check syntax and try again\n";
    exit;
}

Swetest::init($config['swetest']['bin'], $config['swetest']['dir']);

$planets = SysolC::MAIN_PLANETS;
$planets[] = SysolC::MEAN_LUNAR_NODE;
$params = [
    'date'      => '2000-01-01 00:00:00',
    'planets'   => $planets,
];
$coords = Swetest::ephem($params);

echo "\n"; print_r($coords); echo "\n";

/* 

2000-01-01 00:00:00

[sun] => 279.8592145
[moon] => 217.2932841
[mercury] => 271.1118068
[venus] => 240.9614109
[mars] => 327.5754698
[jupiter] => 25.2331333
[saturn] => 40.4058553
[uranus] => 314.7840953
[neptune] => 303.1752620
[pluto] => 251.4371772

UT:  2451544.500000000     delta t: 63.828500 sec
TT:  2451544.500738756
Epsilon (t/m)     23°26'15.6467   23°26'21.4066
Nutation          -0° 0'13.9311   -0° 0' 5.7600
Sun              279.8592144
Moon             217.2932849
Mercury          271.1118066
Venus            240.9614107
Mars             327.5754697
Jupiter          25.2331300
Saturn           40.4058643
Uranus           314.7840708
Neptune          303.1752540
Pluto            251.4371697
*/
