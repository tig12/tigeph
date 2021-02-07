<?php
/******************************************************************************
    Comparision between different ephemeris computation routines.
    Sweph using JPL is considered as the reference implementation.
    Other implementation are tested against Sweph JPL.
    Generates HTML file in tigeph/tmp/bench
    
    @license  GPL
    @history  2021-02-05 15:26:59+01:00, Thierry Graff : Creation
********************************************************************************/

namespace buildeph\bench;

use tigeph\model\SysolC;
use tigeph\ephem\meeus1\Meeus1;
use tigeph\ephem\sweph\Sweph;

class time {
    
    /** Generated HTML **/
    private static $output = '';
    
    /** Dates used to perform the tests **/
    private static $dates = [];
    
    /** Nb of computations **/
    private static $N = 10000;
    
    /** For report - String containing the interval of dates used in the tests **/
    private static $interval = '';
    /** For report - String containing the first date used in the tests **/
    private static $from = '';
    /** For report - String containing the last date used in the tests **/
    private static $to = '';
    
    /** 
        Main function, called by CLI
    **/
    public static function execute($params=[]){
        //
        self::initSweph();
        self::initDays();
        self::pageHeader();
        //
        // Computation
        //
        $t1 = microtime(true);
        foreach(self::$dates as $date){
            $params = [
                'date'      => $date,
                'planets'   => SysolC::MAIN_PLANETS,
            ];
            $swe = Sweph::ephem($params);
        }
        $t2 = microtime(true);
        $dt_sweph = round($t2 - $t1, 2);
        //
        $t1 = microtime(true);
        foreach(self::$dates as $date){
            $params = [
                'date'      => $date,
                'planets'   => SysolC::MAIN_PLANETS,
            ];
            $m1 = Meeus1::ephem($params);
        }
        $t2 = microtime(true);
        $dt_m1 = round($t2 - $t1, 2);
        //
        // Report
        //
        self::$output .= "<div>Test for " . self::$N . " computations.</div>\n";
        self::$output .= "<div>Dates between <b>" . self::$from . "</b> and <b>" . self::$to . "</b></div>\n";
        self::$output .= "<div>(interval: " . self::$interval . ")</div>\n";
        self::$output .= "<table class=\"wikitable margin\">\n";
        self::$output .= "    <tr><th></th><th>time</th><th>%</th></tr>\n";
        self::$output .= "    <tr><td>Sweph</td><td>$dt_sweph s</td><td>100 %</td></tr>\n";
        $p = round(100 * $dt_m1 / $dt_sweph, 2);
        self::$output .= "    <tr><td>Meeus1</td><td>$dt_m1 s</td><td>$p %</td></tr>\n";
        self::$output .= "</table>\n";
        self::pageFooter();
        //
        $outfile = dirname(dirname(__DIR__)) . DS . 'tmp' . DS . 'bench' . DS . 'time.html';
        file_put_contents($outfile, self::$output);
        echo "Wrote content in $outfile\n";
    }
    
    // ******************************************************
    private static function initSweph(){
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
        Sweph::init($config['swetest']['bin'], $config['swetest']['dir']);
    }
    
    // ******************************************************
    /**
        One day every ten years, range 1750 - 2100
    **/
    private static function initDays(){
        $dt = new \Datetime('1800-01-01');
        $interval = new \DateInterval('P10D');
        self::$interval = '10 days';
        $iso = $dt->format('Y-m-d H:i:s');
        self::$from = $iso;
        for($i=0; $i < self::$N; $i++){
            $dt->add($interval);
            $iso = $dt->format('Y-m-d H:i:s');
            self::$dates[] = $iso;
        }
        self::$to = $iso;
    }
    
    // ******************************************************
    private static function pageHeader(){
        self::$output .= <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Test planet computation execution time</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>

<header>
<h1>
    Compare execution time
    <br>Sweph - Meeus1
</h1>
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
