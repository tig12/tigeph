<?php
/******************************************************************************
    Access of SwissEphemeris computations using the swetest program
    Tested with swe_unix_src_2.05.01 (2016-12-15)
    
    Build swetest :
    download ftp://ftp.astro.com/pub/swisseph/swe_unix_src_2.05.01.tar.gz
    cd swe_unix_src_2.05.01/src
    sudo make
    note for version 2.05.01
    sudo make didn't work
    see https://beta.groups.yahoo.com/neo/groups/swisseph/conversations/topics/6299
    this instruction works, and generates swetest :
    cc 0-g -Wall -fPIC     -o swetest swetest.o -L. -lswe -lm -ldl
    ok, swetest generated
    ./swetest -?

    @license    GPL
    @copyright  jetheme.org
    @history    2021-02-02 16:36:39+01:00, Thierry Graff : Integration to tigeph
    @history    2020-12-17 21:49:00+01:00, Thierry Graff : Convert to an autonom program
    @history    2016-12-15 11:09:43+01:00, Thierry Graff : Full implementation of parameters
    @history    2008-06-01 20:19         , Thierry Graff : Creation
********************************************************************************/
namespace tigeph\ephem\swetest;

use tigeph\Ephem;
use tigeph\model\DomC;
use tigeph\model\SysolC;

class Swetest implements Ephem {
    
    /** Path to the swetest binary **/
    private static $SWEBIN;
    
    /** Path to directory containing sweph data files **/
    private static $SWEDIR;
    
    /**
        Correspondance between tigeph domification constants
        and swetest constants used in arguments
    **/
    private const MATCH_ARG_DOMIFICATION = [
        DomC::PLACIDUS         => 'P',
        DomC::KOCH             => 'K',
        DomC::PORPHYRIUS       => 'O',
        DomC::REGIOMONTANUS    => 'R',
        DomC::CAMPANUS         => 'C',
        DomC::AEQUELIS         => 'A',
        DomC::WHOLE_SIGN       => 'W',
    ];
    
    /**
        Correspondance between tigeph planet constants
        and swetest constants used in arguments 
    **/
    private const MATCH_ARG_PLANETS = [
        // p main factors as above, plus main asteroids DEFGHI
        // h ficticious factors J..X
        // z hypothetical body, with number given in -xz
        // s minor planet, with MPC number given in -xs
        // a all factors
        SysolC::SUN       => '0',
        SysolC::MERCURY   => '2',
        SysolC::VENUS     => '3',
        SysolC::EARTH     => 'C',
        SysolC::MOON      => '1',
        SysolC::MARS      => '4',
        SysolC::JUPITER   => '5',
        SysolC::SATURN    => '6',
        SysolC::URANUS    => '7',
        SysolC::NEPTUNE   => '8',
        SysolC::PLUTO     => '9',
        //
        SysolC::MEAN_LUNAR_NODE   => 'm',
        SysolC::TRUE_LUNAR_NODE   => 't',
        SysolC::OBLIQUITY         => 'o',
        SysolC::NUTATION          => 'n',
        SysolC::MEAN_LUNAR_APOGEE => 'A', // black moon
        SysolC::OSCULTATING_LUNAR_APOGEE => 'B', // true black moon ?
        //
        SysolC::CHIRON    => 'D',
        SysolC::PHOLUS    => 'E',
        SysolC::CERES     => 'F',
        SysolC::PALLAS    => 'G',
        SysolC::JUNO      => 'H',
        SysolC::VESTA     => 'I',
        SysolC::CUPIDO    => 'J',
        SysolC::HADES     => 'K',
        SysolC::ZEUS      => 'L',
        SysolC::KRONOS    => 'M',
        // N Apollon 
        // O Admetos 
        // P Vulkanus 
        // Q Poseidon 
        // R Isis (Sevin) 
        // S Nibiru (Sitchin) 
        // T Harrington 
        // U Leverrier's Neptune
        // V Adams' Neptune
        // W Lowell's Pluto
        // X Pickering's Pluton
        // Y Vulcann
        // Z White Moon
    ];
    
    // ******************************************************
    /** Correspondance between planet constants and swetest output **/
    private const MATCH_OUTPUT_PLANETS = [
        'Sun'           => SysolC::SUN,
        'Mercury'       => SysolC::MERCURY,
        'Venus'         => SysolC::VENUS,
        'Moon'          => SysolC::MOON,
        'Mars'          => SysolC::MARS,
        'Jupiter'       => SysolC::JUPITER,
        'Saturn'        => SysolC::SATURN,
        'Uranus'        => SysolC::URANUS,
        'Neptune'       => SysolC::NEPTUNE,
        'Pluto'         => SysolC::PLUTO,
        //
        'mean Node'     => SysolC::MEAN_LUNAR_NODE,
        'true Node'     => SysolC::TRUE_LUNAR_NODE,
        // '' => SysolC::OBLIQUITY,
        //'' => SysolC::NUTATION,
        'mean Apogee'   => SysolC::MEAN_LUNAR_APOGEE, // black moon
        'osc. Apogee'   => SysolC::OSCULTATING_LUNAR_APOGEE, // true black moon ?
        //
        'Ceres'         => SysolC::CERES,
        'Pallas'        => SysolC::PALLAS,
        'Juno'          => SysolC::JUNO,
        'Vesta'         => SysolC::VESTA,
        //
        'Chiron'        => SysolC::CHIRON,
        'Pholus'        => SysolC::PHOLUS,
        // '' => SysolC::CUPIDO,
        // '' => SysolC::HADES,
        // '' => SysolC::ZEUS,
        // '' => SysolC::KRONOS,
        //
    ];
    
    // ******************************************************
    /** Correspondance between house constants and swetest output **/
    private const MATCH_OUTPUT_HOUSES = [
        'house  1' => DomC::H1,
        'house  2' => DomC::H2,
        'house  3' => DomC::H3,
        'house  4' => DomC::H4,
        'house  5' => DomC::H5,
        'house  6' => DomC::H6,
        'house  7' => DomC::H7,
        'house  8' => DomC::H8,
        'house  9' => DomC::H9,
        'house 10' => DomC::H10,
        'house 11' => DomC::H11,
        'house 12' => DomC::H12,
    ];

    // ******************************************************
    /**
        Initialises path to swetest binary and path to directory containing sweph data files
    **/
    public static function init($sweBin, $sweDir){
        Swetest::$SWEBIN = $sweBin;
        Swetest::$SWEDIR = $sweDir;
    }
    
    //***************************************************
    public static function getComputableThings(){
        return [
            SysolC::SUN,
            SysolC::MOON,
            SysolC::MERCURY,
            SysolC::VENUS,
            SysolC::EARTH,
            SysolC::MARS,
            SysolC::JUPITER,
            SysolC::SATURN,
            SysolC::URANUS,
            SysolC::NEPTUNE,
            SysolC::PLUTO,
            SysolC::MEAN_LUNAR_NODE,
            SysolC::TRUE_LUNAR_NODE,
            SysolC::NUTATION,
            SysolC::MEAN_LUNAR_APOGEE,
            SysolC::OSCULTATING_LUNAR_APOGEE,
            //
            SysolC::CHIRON,
            SysolC::PHOLUS,
            SysolC::CERES,
            SysolC::PALLAS,
            SysolC::JUNO,
            SysolC::VESTA,
            SysolC::CUPIDO,
            SysolC::HADES,
            SysolC::ZEUS,
            SysolC::KRONOS,
            SysolC::SUN,
            SysolC::MERCURY,
            SysolC::VENUS,
            SysolC::MOON,
            SysolC::MARS,
            SysolC::JUPITER,
            SysolC::SATURN,
            SysolC::URANUS,
            SysolC::NEPTUNE,
            SysolC::PLUTO,
            //
            SysolC::MEAN_LUNAR_NODE,
            SysolC::TRUE_LUNAR_NODE,
            SysolC::MEAN_LUNAR_APOGEE, // black moon
            SysolC::OSCULTATING_LUNAR_APOGEE, // true black moon ?
            //
            SysolC::CERES,
            SysolC::PALLAS,
            SysolC::JUNO,
            SysolC::VESTA,
            //
            SysolC::CHIRON,
            SysolC::PHOLUS,
            //
            DomC::H1,
            DomC::H2,
            DomC::H3,
            DomC::H4,
            DomC::H5,
            DomC::H6,
            DomC::H7,
            DomC::H8,
            DomC::H9,
            DomC::H10,
            DomC::H11,
            DomC::H12,
        ];
    }
    
    
    //***************************************************
    /**
        Computation using swetest program of Swiss Ephemeris
        See tigeph\Ephem for documentation of parameters $date and $planets, and return type.
        @param $params  Associative array of parameters containing :
            - 'compute-houses'      boolean
                                    optional
                                    default false
            - 'domification-system' use a constant of tigeph\model\DomC
                                    required only if 'compute-houses' = true
            - 'lg'                  longitude of the observer's place, in decimal degrees
                                    required only if 'compute-houses' = true
            - 'lat'                 latitude of the observer's place, in decimal degrees
                                    required only if 'compute-houses' = true
    **/
    public static function ephem (
        $date,
        $what,
        $params=[
            'compute-houses' => false,    
        ],
    ) {
        //             
        // build argument list, depending on this function's arguments
        //
        $args = '';
        // date time
        [$day, $time] = explode(' ', $date);
        $tmp = explode('-', $day);
        $sweDay = $tmp[2] . '.' . $tmp[1] . '.' . $tmp[0];
        $args .= ' -b' . $sweDay . ' -ut' . $time; // ex -b15.12.2016 -ut14:19:55
        // planets
        $args .= ' -p'; // ex -p023C1456789FGIHABmt
        foreach($what as $planet){
            $args .= Swetest::MATCH_ARG_PLANETS[$planet];
        }
        // houses
        if($params['compute-houses']){
            $domificationSystem = Swetest::MATCH_ARG_DOMIFICATION[$params['domification-system']];
            $args .= ' -house' . $params['lg'] . ',' . $params['lat'] . ',' . $domificationSystem; // ex -house12.05,49.50,P
        }
        // ephemeris dir
        $args .= ' -edir' . Swetest::$SWEDIR;
        // output
        $args .= ' -head'; // no header
        $args .= ' -fPl'; // echo name and longitude
        //
        // execute swiss ephem
        //
        $cmd = self::$SWEBIN . $args;
        exec($cmd, $output);
//echo "\n"; print_r(self::$SWEBIN . $args); echo "\n";
//echo "\n<pre>"; print_r($output); echo "</pre>\n";
        //
        // Parse the output and fill returned array
        //
        $res = [
        ];
        // pattern for a line containing one coordinate
        $p1 = '/^(.*?)(-?\d+\.\d+)\s*$/';
        foreach($output as $line){
            preg_match($p1, $line, $matches);
            if(count($matches) == 3){
                $code = trim($matches[1]);
                if(isset(Swetest::MATCH_OUTPUT_PLANETS[$code])){
                    $object = Swetest::MATCH_OUTPUT_PLANETS[$code];
                    $res[$object] = $matches[2];
                }
                else if(isset(Swetest::MATCH_OUTPUT_HOUSES[$code])){
                    $object = Swetest::MATCH_OUTPUT_HOUSES[$code];
                    $res[$object] = $matches[2];
                }
            }
        }
        return $res;
    }
    
    
    // ******************************************************
    /**
        Computations using Swiss Ephemeris of rise / set for a given day
        Returns false if computation is impossible
        @param  $P associative array containing :
            - 'day'                 string DD.MM.YYYY
                                    required
            - 'planets'             array containing the planet codes to compute
                                    required
            - 'lg'                  longitude of the observer's place, in decimal degrees
                                    required
            - 'lat'                 latitude of the observer's place, in decimal degrees
                                    required
            - 'altitude'            altitude of the observer's place, in meters
                                    optional
                                    default 0
        @return associative array that may contain the following elements :
            result['rise']
                map planet code => ISO 8601 date of rise (lever)
            result['set']
                map planet code => ISO 8601 date of set (coucher)
        @todo Code could be simplified, by calling directly swetest with -n2 (this would lead to longer execution time)
    **/
    public static function riseSet($P){
        //
        // build argument list, depending on this function's arguments
        //
        $args = '';
        // date time
        $args .= ' -b' . $P['day']; // ex -b15.12.2016
        // rise / set parameters
        $args .= ' -rise -geopos' . $P['lg'] . ',' . $P['lat'] . ',' . $P['altitude']; // ex -rise -geopos12.05,49.50,250
        // ephemeris dir
        $args .= ' -edir' . Swetest::$SWEDIR;
        // output
        $args .= ' -head'; // no header -- comment this line for easier debug of $output
        //
        // execute swiss ephem
        //
        foreach($what as $planet){
            $args2 = $args . ' -p' .  Swetest::MATCH_ARG_PLANETS[$planet];
            exec(self::$SWEBIN . $args2, $output);
        }
        // parse output and fill result
        $res = [];
        $p_dayhour = '(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2}):(.*?)'; // warning, must be followed by '\s+' to work, because the last '(.*?)' grabs the seconds
        $p1 = '/rise\s+' . $p_dayhour . '\s+set +' . $p_dayhour . '\s+.*/'; // pattern for rise and set
        $p2 = '/rise\s+\-\s+set +' . $p_dayhour . '$/'; // pattern for set only
        $j = 0;
        $missing_rise = [];
        for($i=1; $i < count($output); $i+=2){
            $planet = $what[$j];
            $j++;
            preg_match($p1, $output[$i], $m);
            if(count($m) == 13){
                // rise and set
                $res['rise'][$planet] = $m[3] . '-' . $m[2] . '-' . str_pad($m[1], 2, '0') . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6] . 'Z';
                $res['set'][$planet] = $m[9] . '-' . $m[8] . '-' . str_pad($m[7], 2, '0') . ' ' . $m[10] . ':' . $m[11] . ':' . $m[12] . 'Z';
            }
            else{
                // set only
                $missing_rise[] = $planet;
                preg_match($p2, $output[$i], $m);
                $res['rise'][$planet] = '';
                $res['set'][$planet] = $m[3] . '-' . $m[2] . '-' . str_pad($m[1], 2, '0') . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6] . 'Z';
            }
        }
        if(!empty($missing_rise)){
            // call with -n2
            $args = $args . ' -n2';
            foreach($missing_rise as $planet){
                unset($output);
                $args2 = $args . ' -p' .  Swetest::MATCH_ARG_PLANETS[$planet];
                exec(self::$SWEBIN . $args2, $output);
                preg_match($p1, $output[2], $m);
                if(count($m) == 13){
                    $res['rise'][$planet] = $m[3] . '-' . $m[2] . '-' . str_pad($m[1], 2, '0') . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6] . 'Z';
                }
                else{
                    throw new Exception("Unable to compute rising time for $planet on day " . $P['day']);
                }
            }
        }
        return $res;
    }
    
    
    // ******************************************************
    /**
        Computation of rise / set, for a given date / time, for several planets,
        with the guarantee that, for each planet :
            rise < $P['date'] < set
            or
            set < $P['date'] < rise
        @param  $P associative array of parameters ; 
            Identic to {@link Swetest::riseSet()}  parameters, except that a parameter $P['date'] is used instead of $P['day']
            $P['date'] must be a ISO 8601 date (with day and time information)
    **/
    public static function surroundingRiseSet($P){
        //
        // build argument list, depending on this function's arguments
        // swetest is called for the day before, for 3 consecutive days => sure to have surrounding
        //
        $args = '';
        // date time
        $dt = new DateTime($P['date']);
//        $dt->setTimezone(new DateTimeZone('UTC')); // converted in utc for swetest
        $date_utc = $dt->format('Y-m-d H:i:s');
        $dt->sub(new DateInterval('P1D'));
        $day = $dt->format('j.n.Y');
        $args .= ' -b' . $day; // ex -b15.12.2016
        // rise / set parameters
        $args .= ' -rise -geopos' . $P['lg'] . ',' . $P['lat'] . ',' . $P['altitude']; // ex -rise -geopos12.05,49.50,250
        // ephemeris dir
        $args .= ' -edir' . Swetest::$SWEDIR;
        // -n2 : compute for 3 consecutive days
        $args .= ' -n3';
        // output
//        $args .= ' -head'; // no header -- comment this line for easier debug of $output
        //
        // execute swiss ephem
        //                                                                                                                                                         
        $res = [];
        // warning, must be followed by '\s+' to work, because the last '(.*?)' grabs the seconds
        $p_dayhour = '(\d{1,2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2}):(.*?)';
        $p1 = '/rise\s+' . $p_dayhour . '\s+set +' . $p_dayhour . '\s+.*/'; // pattern for rise and set
        $p2 = '/rise\s+\-\s+set +' . $p_dayhour . '$/'; // pattern for set only
        foreach($what as $planet){
            unset($output);
            $dates = [];
            $args2 = $args . ' -p' .  Swetest::MATCH_ARG_PLANETS[$planet];
            exec(self::$SWEBIN . $args2, $output);
            for($i=1; $i < 4; $i++){ // loop on the lines of results
                preg_match($p1, $output[$i], $m);
                if(count($m) == 13){
                    // rise and set
                    $dates[] = $m[3] . '-' . $m[2] . '-' . str_pad($m[1], 2, '0') . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6] . 'Z' . 'R';
                    $dates[] = $m[9] . '-' . $m[8] . '-' . str_pad($m[7], 2, '0') . ' ' . $m[10] . ':' . $m[11] . ':' . $m[12] . 'Z' . 'S';
                }
                else{
                    // set only
                    preg_match($p2, $output[$i], $m);
                    if(count($m) == 7){
                        $dates[] = $m[3] . '-' . $m[2] . '-' . str_pad($m[1], 2, '0') . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6] . 'Z' . 'S';
                    }
                    else{
                        throw new Exception("Unable to compute rising time for $planet on day " . $P['date']);
                    }
                }
            }
            // $dates are sorted, so it's easy to extract the surrounding dates
            for($i=1; $i < count($dates); $i++){
                if($date_utc > $dates[$i-1] && $date_utc < $dates[$i]){
                    if(substr($dates[$i-1], -1) == 'R'){
                        $res['rise'][$planet] = substr($dates[$i-1], 0, -1);
                    }
                    else{
                        $res['set'][$planet] = substr($dates[$i-1], 0, -1);
                    }
                    if(substr($dates[$i], -1) == 'R'){
                        $res['rise'][$planet] = substr($dates[$i], 0, -1);
                    }
                    else{
                        $res['set'][$planet] = substr($dates[$i], 0, -1);
                    }
                    break;
                }
            }
        }
        return $res;
    }
    
}//end class

