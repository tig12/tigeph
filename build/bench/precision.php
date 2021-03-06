<?php
/******************************************************************************
    Comparision between different ephemeris computation routines.
    Swetest using JPL is considered as the reference implementation.
    Other implementation are tested against Swetest JPL.
    Generates HTML file in tigeph/tmp/bench
    
    @license  GPL
    @history  2021-02-05 15:26:59+01:00, Thierry Graff : Creation
********************************************************************************/

namespace buildeph\bench;

use tigeph\model\SysolC;
use tigeph\ephem\meeus1\Meeus1;
use tigeph\ephem\meeusmall\Meeusmall;
use tigeph\ephem\swetest\Swetest;

class precision {
    
    /** Generated HTML **/
    private static $output = '';
    
    /** Dates used to perform the tests **/
    private static $dates = [];
    
    /** 
        Main function, called by CLI
    **/
    public static function execute($params=[]){
        //
        self::initSwetest();
        self::initDays();
        self::pageHeader();
        //
        self::$output .= "<table class=\"wikitable\">\n";
        self::$output .= "    <tr><th></th><th>Swetest</th><th>&Delta; Meeus1</th><th>&Delta; Meeusmall</th></tr>\n";
        //
        $planets = SysolC::MAIN_PLANETS;
        $planets[] = SysolC::MEAN_LUNAR_NODE;
        //
        foreach(self::$dates as $date){
            self::$output .= "    <tr><td colspan=\"3\"><b>$date</b></td></tr>\n";
            $params = [
            ];
            //
            $swe = Swetest::ephem($date, $planets);
            //
            $m1 = Meeus1::ephem($date, $planets);
            //
            $msmall = Meeusmall::ephem($date, $planets);
            //
            foreach($planets as $pl){
                if($pl == SysolC::EARTH){
                    continue;
                }
                self::$output .= "<tr>";
                self::$output .= "<td class=\"padding-left\">$pl</td>";
                self::$output .= "<td>" . $swe['planets'][$pl] . "</td>";
                $dm1 = round(abs($swe['planets'][$pl] - $m1['planets'][$pl]), 4);
                $dmsmall = round(abs($swe['planets'][$pl] - $msmall['planets'][$pl]), 4);
                self::$output .= "<td>$dm1</td>";
                self::$output .= "<td>$dmsmall</td>";
                self::$output .= "</tr>\n";
            }
        }
        //
        self::$output .= "</table>\n";
        self::pageFooter();
        //
        $outfile = dirname(dirname(__DIR__)) . DS . 'tmp' . DS . 'bench' . DS . 'precision.html';
        file_put_contents($outfile, self::$output);
        echo "Wrote content in $outfile\n";
    }
    
    // ******************************************************
    private static function initSwetest(){
        $filename = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'config.yml';
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
    }
    
    // ******************************************************
    /**
        One day every ten years, range 1750 - 2100
    **/
    private static function initDays(){
        $dt = new \Datetime('1740-01-01');
        $interval = new \DateInterval('P10Y');
        $iso = $dt->format('Y-m-d H:i:s');
        while($iso < '2100'){
            $dt->add($interval);
            $iso = $dt->format('Y-m-d H:i:s');
            self::$dates[] = $iso;
        }
    }
    
    // ******************************************************
    private static function pageHeader(){
        self::$output .= <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Test planet computation precision</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>

<header>
<h1>Compare precision Meeus1 - Swiss ephemeris</h1>
</header>

<article>

HTML;
    }
    
    // ******************************************************
    private static function pageFooter(){
        self::$output .= <<<HTML
</article>
</body>
</html>

HTML;
    }
    
} // end class
