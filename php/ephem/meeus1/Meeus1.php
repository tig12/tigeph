<?php 
/********************************************************************************
    Astronomical computations from old Meeus' book (Astronomical Formulae for Calculators, third edition).
    
    @license    GPL
    @history 2021-02-02 17:17:18+01:00, Thierry Graff : Integration to tigeph
    @history 2007-02-05,                Thierry Graff : PHP4 to PHP5 port
    @history 2002-10-29,                Thierry Graff : C to PHP4 port
    @history 1997-05,                   Thierry Graff : Pascal to C port (Pascal sources given by David Coronat - www.pholos.com)
    
    @todo Optimization using T2, T3 in calcXXX functions;
    @todo Change T2 in calcPlanets, to avoid name confusion with $this->T2
****************************************************************************************/
namespace tigeph\ephem\meeus1;

use tigeph\Ephem;
use tigeph\model\SpaceTimeC;
use tigeph\model\SolarFramesC;
use tigeph\model\SysolC;
use tigeph\ephem\JulianDay;

class Meeus1 implements Ephem {
    
    //********************* Instance variables ******************************
    private $planets;
    /** Julian days **/
    private $jd;
    /** Nb of julian centuries since 1900-01.0.5 **/
    private $T, $T2, $T3;
    /** Mean anomalies **/
    private $mas='';
    /** Variables for the computation of gazeous planets **/
    private $gaz='';
    /** Position of the Earth, in heliocentric ecliptic frame, cartesian coordinates **/
    private $geomEarth_cart='';
    
    
    //***************************************************
    /**
        Constructor
        @param $planets Codes of the planets, using SysolC constants.
        @param $jd Julian day
    **/
    public function __construct($planets, $jd){
        $this->planets = $planets;
        $this->jd = $jd;
        $this->T = ($jd-2415020.0)/36525.0;
        $this->T2 = $this->T * $this->T;
        $this->T3 = $this->T2 * $this->T;
        $this->mas = $this->calcMeanAnomalies();
        if(count(array_intersect($planets, SysolC::GAZEOUS_PLANETS)) != 0){
            $this->gaz = $this->calcGaz($this->T);
        }
    }
    
    
    //***************************************************
    /** Returns an array of planets (using SysolC) the theory is able to compute. **/
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
        ];
    }
    
    /** 
        Computation using Meeus1 routines
        See tigeph\Ephem for documentation of parameters $date and $planets, and return type.
    **/
    public static function ephem(
        $date,
        $what,
        $params=[],
    ) {
        $jd = JulianDay::isoDate2jd($date);
        $frame=SolarFramesC::EC;
        $sphereCart=SpaceTimeC::SPHERICAL;
        $onlyLongitude=true;
        $meeus = new Meeus1($what, $jd);
        return ['planets' => $meeus->calcPlanets($frame, $sphereCart)];
    }
    
    
    //***************************************************
    /**
        Computation of coordinates of one or several planets, using Meeus' computation.
        SysolC::MEAN_LUNAR_NODE computed only if geocentric ecliptic coordinates are demanded
        @param  $frame
                The frame in which the coordinates must be expressed, 
                using constants of SolarFramesC ; Geocentric Ecliptic by default.
        @param  $sphereCart Expression mode of the coords (spherical / cartesian),
                using constants of SpaceTimeC ; spherical by default.
        @param  $onlyLongitude
                if EC SPHERICAL (r, l, b) is demanded,
                don't return an array of Vector3 but an array of doubles containing longitudes of planets.
                
        @return An associative array. The keys contain the planet constants of SysolC.
                The values contain the coordinates (instances of Vector3).
    **/
    public function calcPlanets(
        $frame=SolarFramesC::EC,
        $sphereCart=SpaceTimeC::SPHERICAL,
        $onlyLongitude=true,
    ){
        if(is_numeric($this->planets)){
            $this->planets = array($this->planets);
        }
        // TO DO : check coherence of param : helio + sun or geo + earth) ; moon : only geo
        if($this->planets == ""){
            $this->planets = self::getComputableThings();
            if($frame == SolarFramesC::HELIO){
                $this->planets[] = SysolC::EARTH;
            }
            if($frame == SolarFramesC::EC){
                $this->planets[] = SysolC::SUN;
            }
        }
        $T = ($this->jd-2415020.0)/36525.0; // nb of julian centuries since 1900-01.0.5
        $mas = self::calcMeanAnomalies($T);
        //
        // Compute Earth's geometric heliocentric ecliptic position
        $geomEarth = self::calcEarth($T, $mas); // Ec Helio Geometric.
        $this->geomEarth_cart = Meeus1x::sphereToCart($geomEarth);
        // Compute Earth's aparent heliocentric ecliptic position - Spherical
        $dt = $geomEarth->x1 * SysolC::KM_PER_AU / SpaceTimeC::LIGHT_VELOCITY; // time taken by velocity to go from Earth to Sun.
        $dt /= SpaceTimeC::SECONDS_PER_DAY; // convert to days
        $T2 = ($this->jd-$dt-2415020.0)/36525.0; // new time
        $mas2 = self::calcMeanAnomalies($T2);
        $appEarth = self::calcEarth($T2, $mas2); // Ec helio.
        //
        // if only sun or earth required, return results
        if(count($this->planets) == 1){
            if($this->planets[0] == SysolC::EARTH && $frame == SolarFrames::HELIO){
                return array(EARTH => $geomEarth); // Ec helio geom
            }
            if($this->planets[0] == SysolC::SUN && $frame == EC){
                // to do : apply precession and nutation
                // to do : handle $sphereCart
                return array(SysolC::SUN => new Vector3($appEarth->x1, Meeus1x::mod360($appEarth->x2 - 180), -$appEarth->x3));
            }
        }
        //
        // Compute the planets' Ecliptic geometric heliocentric coords
        $gaz = self::calcGaz($T, $this->planets); // variables for gazeous planets
        //
        // build $h (h stands for helio)
        //
        $h = [];
        foreach($this->planets as $pl){
            if( in_array($pl, self::getComputableThings())
                && $pl != SysolC::MEAN_LUNAR_NODE
                && (
                    ($frame != SolarFramesC::HELIO && $pl != SysolC::SUN && $pl != SysolC::MOON)
                 || ($frame == SolarFramesC::HELIO && $pl != SysolC::EARTH)
                )
            ){
                $h[$pl] = self::calcPlanet($pl, $T, $mas, $this->geomEarth_cart, $gaz);
            }
        }
        //
        // If heliocentric positions required, return results.
        if($frame == SolarFramesC::HELIO){
            if (in_array(SysolC::EARTH, $this->planets)){
                $h[SysolC::EARTH] = $geomEarth;
            }
            // TO DO : handle the Moon
            return $h;
        }
        //
        // Change from geometric to apparent (for the Earth, already done with $appEarth)
        $h2 = [];
        foreach($h as $planet => $val){
            if($planet != SysolC::EARTH){
                $tmp = Meeus1x::sphereToCart($val);
                $rho = Vector3::sub($tmp, $this->geomEarth_cart)->norm(); // dist planet - Earth
                $dt = $rho * SysolC::KM_PER_AU / SpaceTimeC::LIGHT_VELOCITY; // time taken by velocity to go from planet to Earth.
                $dt /= SpaceTimeC::SECONDS_PER_DAY; // convert to days
                $T2 = ($this->jd-$dt-2415020.0)/36525.0; // new time
                $mas2 = self::calcMeanAnomalies($T2);
                $gaz2 = self::calcGaz($T2, $this->planets);
                $h2[$planet] = self::calcPlanet($planet, $T2, $mas2, $this->geomEarth_cart, $gaz2); // apparent planet - Ec helio.
            }
        }
        //
        // Change from heliocentric to geocentric
        // $g contains geocentric positions
        //
        // Handle the sun - $appEarth is still in spherical
        $g = [];
        if(in_array(SysolC::SUN, $this->planets)){
            $g[SysolC::SUN] = new Vector3($appEarth->x1, Meeus1x::mod360($appEarth->x2 - 180), -$appEarth->x3); // spherical
            if($sphereCart == SpaceTimeC::CARTESIAN){
                $g[SysolC::SUN] = Meeus1x::sphereToCart($g[SysolC::SUN]);
            }
        }
        if(in_array(SysolC::MOON, $this->planets)){
            $g[SysolC::MOON] = self::calcMoon($T);
        }
        // Hanlde the planets
        foreach($h2 as $planet => $val){
            if($planet != SysolC::SUN){
                $g[$planet] = Vector3::sub(Meeus1x::sphereToCart($val), $this->geomEarth_cart); // result in cartesian
                if($sphereCart == SpaceTimeC::SPHERICAL){
                    $g[$planet] = Meeus1x::cartToSphere($g[$planet]);
                    // $g[$planet] = (r, l, b) ; convert l and b to degrees
                    $g[$planet]->x2 = $g[$planet]->x2 / Meeus1x::PIs180;
                    $g[$planet]->x3 = $g[$planet]->x3 / Meeus1x::PIs180;
                }
            }
        }
        if(in_array(SysolC::MEAN_LUNAR_NODE, $this->planets)){
            $g[SysolC::MEAN_LUNAR_NODE] = new Vector3(0, self::calcMeanLunarNode(), 0);
        }
        //
        // to do : apply precession and nutation
        //
        //if($frame == EC)
        //
        // to do : conversion Ec / Eq
        //
        // keep only 3 digits
        foreach($g as $k => $v){
            $g[$k]->x2 = round($g[$k]->x2, 3);
        }
        //
        if($onlyLongitude){
            $res = [];
            foreach($g as $k => $v){
                $res[$k] = $v->x2; // keep only longitude
            }
            return $res;
        }
        return $g;
    }

    
    //***************************************************
    /** Calls one of the calcXXX function, depending on parameter $whichPlanet.
        cf chap 18 of Meeus.
        @param $whichPlanet The index of the planet to compute, as defined in AstronomyConstants.php.
    **/
    private function calcPlanet($whichPlanet){
        switch($whichPlanet){
            case SysolC::MOON            : return $this->calcMoon();          break;
            case SysolC::MERCURY         : return $this->calcMercury();       break;
            case SysolC::VENUS           : return $this->calcVenus();         break;
            case SysolC::EARTH           : return $this->calcEarth();         break;
            case SysolC::MARS            : return $this->calcMars();          break;
            case SysolC::JUPITER         : return $this->calcJupiter();       break;
            case SysolC::SATURN          : return $this->calcSaturn();        break;
            case SysolC::URANUS          : return $this->calcUranus();        break;
            case SysolC::NEPTUNE         : return $this->calcNeptune();       break;
            case SysolC::PLUTO           : return $this->calcPluto();         break;
            case SysolC::MEAN_LUNAR_NODE : return $this->calcMeanLunarNode(); break;
        }
    }
    
    
    //***************************************************
    /** Computation of the mean anomalies, from Mercury to Neptune.
    Indices correspond to Meeus
    **/
    private function calcMeanAnomalies(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $M1 = Meeus1x::mod360(102.27938 + (149472.51529 + 7.0E-06*$T)*$T);
        $M2 = Meeus1x::mod360(212.60322 + (58517.80387 + 0.001286*$T)*$T);
        $M3 = Meeus1x::mod360(358.47583 + (35999.04975 - (0.000150 + 0.0000033*$T)*$T)*$T); // Sun, not Earth
        $M4 = Meeus1x::mod360(319.51913 + (19139.85475 + 0.000181*$T)*$T);
        $M5 = Meeus1x::mod360(225.32833 + (3034.69202 - 0.000722*$T)*$T);
        $M6 = Meeus1x::mod360(175.46622 + (1221.55147 - 0.000502*$T)*$T);
        $M7 = Meeus1x::mod360(72.648778 + (428.3791132 + 0.0000788*$T)*$T);
        $M8 = Meeus1x::mod360(37.73063    + (218.4613396 - 0.00007*$T)*$T);
        return array($M1, $M2, $M3, $M4, $M5, $M6, $M7, $M8);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of the Earth - spherical coordinates.
        cf chap 18 of Meeus.
    **/
    private function calcEarth(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $mas = $this->mas;
        $M3 = $mas[2];
        $L = 279.6966778 + (36000.768925 + 0.0003025*$T)*$T;
        $L = Meeus1x::mod360($L);
        $M3_rad = $M3*Meeus1x::PIs180;
        $ecc = 0.01675104 - (0.0000418 + 0.000000126*$T)*$T;
        //
        $center = (1.919460 + (-0.004789 - 0.000014*$T)*$T)*sin($M3_rad)
                + (0.020094 - 0.000100*$T)*sin(2*$M3_rad)
                +    0.000293*sin(3*$M3_rad);
        //
        $lsol = $L + $center;
        $rsol = 1.0000002*(1-$ecc*$ecc)/(1+$ecc*cos($M3_rad+$center*Meeus1x::PIs180));
        //
        //************ Better precision ********************
        $a = (153.23 + 22518.7541 * $T ) * Meeus1x::PIs180;
        $b = (216.57 + 45037.5082 * $T ) * Meeus1x::PIs180;
        $c = (312.69 + 32964.3577 * $T ) * Meeus1x::PIs180;
        $d = (350.74 + (445267.1142    - 0.00144*$T)*$T) * Meeus1x::PIs180;
        $e = (231.19 + 20.2 * $T ) * Meeus1x::PIs180;
        $h = (353.40 + 65928.7155*$T) * Meeus1x::PIs180;
        //
        $rsol = $rsol + 0.00000543*sin($a) + 0.00001575*sin($b)
                    + 0.00001627*sin($c) + 0.00003076*cos($d)
                    + 0.00000927*sin($h);
        //
        $lsol = $lsol    + 0.00134 * cos($a) + 0.00154 * cos($b)
                 + 0.00200 * cos($c) + 0.00179 * sin($d)
                 + 0.00178 * sin($e);
        /*
        Corrections brought by David (not in Meeus).
        - 0.00569 - 0.00479 * sin(nl )
        + (-(17.2327 + 0.01737 * t ) * sin(nl )
        - (1.2729 + 0.00013 * t ) * sin(2 * $l ) + 0.2088 * sin(2 * nl )
        - 0.2037 * sin(2 * l1 ) + (0.1261 - 0.00031 * t ) * sin(m ) + 0.0675 * sin(mm )
        - (0.0497 - 0.00012 * t ) * sin(2 * $l    + m ) - 0.0342 * sin(2 * l1    - nl )
        - 0.0261 * sin(2 * l1    + mm ) + 0.0214 * sin(2 * $l    - m )
        - 0.0149 * sin(2 * $l    - 2 * l1    + mm ) + 0.0124 * sin(2 * $l    - nl )
        + 0.0114 * sin(2 * l1    - mm ))/3600.0;
        **/
        //
        $pos = new Vector3($rsol, Meeus1x::mod360($lsol-180), 0.0);
        // for Pluto, we need the Earth's geometric heliocentric ecliptic position
        // so we keep it in memory here
        if(in_array(SysolC::PLUTO, $this->planets)){
        }
        return $pos;
    }
    
    
    //***************************************************
    /** 
        Computes the geocentric ecliptic coordinates of the Moon - spherical coordinates.
        cf chap 30 of Meeus.
    **/
    private function calcMoon(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $l1=(270.434164+481267.8831*$T-0.001133*$T2+1.9E-06*$T3);
        $m=(358.475833+35999.0498*$T-0.00015*$T2-3.3E-06*$T3)*Meeus1x::PIs180;
        $mm=(296.104608+477198.8491*$T+0.009192*$T2-0.0000144*$T3)*Meeus1x::PIs180;
        $d=(350.737486+445267.1142*$T-0.001436*$T2+1.9E-06*$T3)*Meeus1x::PIs180;
        $f=(11.250889+483202.0251*$T-0.003211*$T2-3.0E-07*$T3)*Meeus1x::PIs180;
        $nl=(259.183275-1934.142*$T+0.002078*$T2+2.2E-06*$T3)*Meeus1x::PIs180;
        $l1=$l1+(0.000233*sin((51.2+20.2*$T)*Meeus1x::PIs180)
            +0.003964*sin((346.56+132.87*$T-0.0091731*$T2)*Meeus1x::PIs180))*Meeus1x::PIs180;
        $m=$m-(0.001778*sin((51.2+20.2*$T)*Meeus1x::PIs180))*Meeus1x::PIs180;
        $mm=$mm+(0.000817*sin((51.2+20.2*$T)*Meeus1x::PIs180)
            +0.003964*sin((346.56+132.87*$T-0.0091731*$T2)*Meeus1x::PIs180))*Meeus1x::PIs180;
        $d=$d+(0.002011*sin((51.2+20.2*$T)*Meeus1x::PIs180)
            +0.003964*sin((346.56+132.87*$T-0.0091731*$T2)*Meeus1x::PIs180))*Meeus1x::PIs180;
        $f=$f+(0.003964*sin((346.56+132.87*$T-0.0091731*$T2)*Meeus1x::PIs180))*Meeus1x::PIs180;
        $l1=$l1+(0.001964*sin($nl));
        $mm=$mm+(0.002541*sin($nl))*Meeus1x::PIs180;
        $d=$d+(0.001964*sin($nl))*Meeus1x::PIs180;
        $f=$f-((0.024691*sin($nl))-(0.004328*sin($nl+(275.05-2.3*$T)*Meeus1x::PIs180)))*Meeus1x::PIs180;
        $e=1-0.002495*$T-7.52E-06*$T2; // correction 2007.02.05 : replaced t by $T 
        $l1=$l1+(6.28875*sin($mm))
                     +(1.274018*sin(2*$d-$mm))
                     +(0.658309*sin(2*$d))
                     +(0.213616*sin(2*$mm))
                     -(0.185596*sin($m))*$e
                     -(0.114336*sin(2*$f))
                     +(0.058793*sin(2*$d-2*$mm))
                     +(0.057212*sin(2*$d-$m-$mm))*$e
                     +(0.05332*sin(2*$d+$mm))
                     +(0.045874*sin(2*$d-$m))*$e
                     +(0.041024*sin($mm-$m))*$e
                     -(0.034718*sin($d))
                     -(0.030465*sin($m+$mm))*$e
                     +(0.015326*sin(2*$d-2*$f))
                     -(0.012528*sin(2*$f+$mm))
                     -(0.01098*sin(2*$f-$mm))
                     +(0.010674*sin(4*$d-$mm))
                     +(0.010034*sin(3*$mm))
                     +(0.008548*sin(4*$d-2*$mm))
                     -(0.00791*sin($m-$mm+2*$d))*$e
                     -(0.006783*sin(2*$d+$m))*$e
                     +(0.005162*sin($mm-$d))
                     +(0.005*sin($m+$d))*$e
                     +(0.004049*sin($mm-$m+2*$d))*$e
                     +(0.003996*sin(2*$mm+2*$d))
                     +(0.003862*sin(4*$d))
                     +(0.003665*sin(2*$d-3*$mm))
                     +(0.002695*sin(2*$mm-$m))*$e
                     +(0.002602*sin($mm-2*$f-2*$d))
                     +(0.002396*sin(2*$d-$m-2*$mm))*$e
                     -(0.002349*sin($mm+$d))
                     +(0.002249*sin(2*$d-2*$m))*$e*$e
                     -(0.002125*sin(2*$mm+$m))*$e
                     -(0.002079*sin(2*$m))*$e*$e
                     +(0.002059*sin(2*$d-$mm-2*$m))*$e*$e
                     -(0.001773*sin($mm+2*$d-2*$f))
                     -(0.001595*sin(2*$f+2*$d))
                     +(0.00122*sin(4*$d-$m-$mm))*$e
                     -(0.00111*sin(2*$mm+2*$f))
                     +(0.000892*sin($mm-3*$d))
                     -(0.000811*sin($m+$mm+2*$d))*$e
                     +(0.000761*sin(4*$d-$m-2*$mm))*$e
                     +(0.000717*sin($mm-2*$m))*$e*$e
                     +(0.000704*sin($mm-2*$m-2*$d))*$e*$e
                     +(0.000693*sin($m-2*$mm+2*$d))*$e
                     +(0.000598*sin(2*$d-$m-2*$f))*$e
                     +(0.00055*sin($mm+4*$d))
                     +(0.000538*sin(4*$mm))
                     +(0.000521*sin(4*$d-$m))*$e
                     +(0.000486*sin(2*$mm-$d));
        return new Vector3(0, Meeus1x::mod360($l1), 0);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Mercury - spherical coordinates. **/
    private function calcMercury(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $mas = $this->mas;
        $M1 = $mas[0];
        $M2 = $mas[1];
        $M5 = $mas[4];
        $LM = 178.179078 + (149474.07078 + 0.0003011*$T)*$T;
        $LM = Meeus1x::mod360($LM);
        $a = 0.3870986;
        $ecc = 0.20561421 + (0.00002046 - 3.0E-08*$T)*$T;
        $i = 7.002881 + (0.0018608 - 0.0000183*$T)*$T;
        $i = Meeus1x::mod360($i);
        $om = 47.145944 + (1.1852083 + 0.0001739*$T)*$T;
        $om = Meeus1x::mod360($om);
        //
        $E = Meeus1x::keplerEq($ecc, $M1*Meeus1x::PIs180); //E in radians
        $nu = 2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2)))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        //
        $r = $a*(1-$ecc*cos($E))
             + 7.525E-06*cos((2*$M5-$M1+53.013)*Meeus1x::PIs180)
             + 6.802E-06*cos((5*$M2-3*$M1-259.918)*Meeus1x::PIs180)
             + 5.457E-06*cos((2*$M2-2*$M1-71.188)*Meeus1x::PIs180)
             + 3.569E-06*cos((5*$M2-$M1-77.75)*Meeus1x::PIs180);
        //
        $u = $LM + $nu - $M1 - $om;                                                                                          
        $u = Meeus1x::mod360($u);
        //
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180), cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = $l + 0.00204*cos((5*$M2-2*$M1+12.220)*Meeus1x::PIs180)
             + 0.00103*cos((2*$M2-$M1-160.692)*Meeus1x::PIs180)
             + 0.00091*cos((2*$M5-$M1-37.003)*Meeus1x::PIs180)
             + 0.00078*cos((5*$M2-3*$M1+10.137)*Meeus1x::PIs180);
        $l = Meeus1x::mod360($l);
        //
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        //
        return new Vector3($r, $l, $b);
    }
    
    
    //********************* calcVenus ******************************
    /** Computes the heliocentric ecliptic coordinates of Venus - spherical coordinates. **/
    private function calcVenus(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $mas = $this->mas;
        $M2 = $mas[1];
        $M3 = $mas[2];
        $M5 = $mas[4];
        $LM = 342.767053 + (58519.21191 + 0.0003097*$T)*$T
             + 0.00077*sin((237.24+150.27*$T)*Meeus1x::PIs180);
        $LM = Meeus1x::mod360($LM);
        $a = 0.7233316;
        $ecc = 0.00682069 + (-0.00004774 + 9.1E-08*$T)*$T;
        $i = 3.393631 + (0.0010058 - 1.0E-06*$T)*$T;
        $om = 75.779647 + (0.89985 + 0.00041*$T)*$T;
        //
        $M2 = $M2 + 0.00077*sin((237.24+150.27*$T)*Meeus1x::PIs180);
        //
        $E = Meeus1x::keplerEq($ecc,$M2*Meeus1x::PIs180); // $E in radians
        //
        $nu = (2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2))))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        //
        $r = $a*(1-$ecc*cos($E))
        + 0.000022501*cos((2*$M3-2*$M2-58.208)*Meeus1x::PIs180)
        + 0.000019045*cos((3*$M3-3*$M2+92.577)*Meeus1x::PIs180)
        + 6.887E-06*cos(($M5-$M2-118.09)*Meeus1x::PIs180)
        + 5.172E-06*cos(($M3-$M2-29.11)*Meeus1x::PIs180)
        + 3.62E-06*cos((5*$M3-4*$M2-104.208)*Meeus1x::PIs180)
        + 3.283E-06*cos((4*$M3-4*$M2+63.513)*Meeus1x::PIs180)
        + 3.074E-06*cos((2*$M5-2*$M2-55.167)*Meeus1x::PIs180);
        //
        $u = $LM + $nu - $M2 - $om;
        $u = Meeus1x::mod360($u);
        //
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180+$om;
        $l = $l + 0.00313*cos((2*$M3-2*$M2-148.225)*Meeus1x::PIs180)
        +0.00198*cos((3*$M3-3*$M2+2.565)*Meeus1x::PIs180)
        +0.00136*cos(($M3-$M2-119.107)*Meeus1x::PIs180)
        +0.00096*cos((3*$M3-2*$M2-135.912)*Meeus1x::PIs180)
        +0.00082*cos(($M5-$M2-208.087)*Meeus1x::PIs180);
        $l = Meeus1x::mod360($l);
        //
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        //
        return new Vector3($r, $l, $b);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Mars - spherical coordinates. **/
    private function calcMars(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $mas = $this->mas;
        $M2 = $mas[1]; // addition 2007.02.06
        $M3 = $mas[2];
        $M4 = $mas[3];
        $M5 = $mas[4];
        $LM = 293.737334 + (19141.69551 + 0.0003107*$T)*$T
        - 0.01133*sin((3*$M5-8*$M4+4*$M3)*Meeus1x::PIs180)
        - 0.00933*cos((3*$M5-8*$M4+4*$M3)*Meeus1x::PIs180);
        $LM = Meeus1x::mod360($LM);
        $a = 1.5236883;
        $ecc = 0.09331290 + (0.000092064 - 7.7E-08*$T)*$T;
        $i = 1.850333 + (-0.0006750 + 0.0000126*$T)*$T;
        $om = 48.786442+ (0.7709917 +(-1.4E-06 - 5.33E-06*$T)*$T)*$T;
        //
        $M4 = $M4 - 0.01133*sin((3*$M5-8*$M4+4*$M3)*Meeus1x::PIs180)
        - 0.00933*cos((3*$M5-8*$M4+4*$M3)*Meeus1x::PIs180);
        //
        $E = Meeus1x::keplerEq($ecc,$M4*Meeus1x::PIs180); // $E in radians.
        //
        $nu = (2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2))))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        //
        $r = $a*(1-$ecc*cos($E))
        +0.000053227*cos(($M5-$M4+41.1306)*Meeus1x::PIs180)
        +0.000050989*cos((2*$M5-2*$M4-101.9847)*Meeus1x::PIs180)
        +0.000038278*cos((2*$M5-$M4-98.3292)*Meeus1x::PIs180)
        +0.000015996*cos(($M3-$M4-55.555)*Meeus1x::PIs180)
        +0.000014764*cos((2*$M3-3*$M4+68.622)*Meeus1x::PIs180)
        +8.966E-06*cos(($M5-2*$M4+43.615)*Meeus1x::PIs180)
        +7.914E-06*cos((3*$M5-2*$M4-139.737)*Meeus1x::PIs180)
        +7.004E-06*cos((2*$M5-3*$M4-102.888)*Meeus1x::PIs180)
        +6.62E-06*cos(($M3-2*$M4+113.202)*Meeus1x::PIs180)
        +4.93E-06*cos((3*$M5-3*$M4-76.243)*Meeus1x::PIs180)
        +4.693E-06*cos((3*$M3-5*$M4+190.603)*Meeus1x::PIs180)
        +4.571E-06*cos((2*$M3-4*$M4+244.702)*Meeus1x::PIs180)
        +4.409E-06*cos((3*$M5-$M4-115.828)*Meeus1x::PIs180);
        //
        $u = $LM + $nu - $M4 - $om;
        $u = Meeus1x::mod360($u);
        //
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = $l     +0.00705*cos(($M5-$M4-48.958)*Meeus1x::PIs180)
        +0.00607*cos((2*$M5-$M4-188.35)*Meeus1x::PIs180)
        +0.00445*cos((2*$M5-2*$M4-191.897)*Meeus1x::PIs180)
        +0.00388*cos(($M3-2*$M4+20.495)*Meeus1x::PIs180)
        +0.00238*cos(($M3-$M4+35.097)*Meeus1x::PIs180)
        +0.00204*cos((2*$M3-3*$M4+158.638)*Meeus1x::PIs180)
        +0.00177*cos((3*$M4-$M2-57.602)*Meeus1x::PIs180)
        +0.00136*cos((2*$M3-4*$M4+154.093)*Meeus1x::PIs180)
        +0.00104*cos(($M5+17.618)*Meeus1x::PIs180);
        $l = Meeus1x::mod360($l);
        //
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        //
        return new Vector3($r, $l, $b);
    }
    
    
    //***************************************************
    /** Computes the variables used for the computation of gazeous planets.
    Apart from $up, all the variables are converted to radians.
    Notations : $up = upsilon, $z = zeta.
                            $sv = sin($V), $s2v = sin(2*$V) etc...
    **/
    private function calcGaz(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $up = $T/5.0 + 0.1;
        $up2 = $up*$up;
        $P = (237.47555 + 3034.9061*$T) * Meeus1x::PIs180;
        $Q = (265.91650 + 1222.1139*$T) * Meeus1x::PIs180;
        $sq = sin($Q);
        $s2q = sin(2.0*$Q);
        $s3q = sin(3.0*$Q);
        $s4q = sin(4.0*$Q);
        $cq = cos($Q);
        $c2q = cos(2.0*$Q);
        $c3q = cos(3.0*$Q);
        $c4q = cos(4.0*$Q);
        $S = (243.51721 + 428.4677*$T) * Meeus1x::PIs180;
        $V = 5*$Q - 2*$P;
        $sv = sin($V);
        $s2v = sin(2.0*$V);
        $cv = cos($V);
        $c2v = cos(2.0*$V);
        $W = 2*$P - 6*$Q + 3*$S;
        $sw = sin($W);
        $z = $Q - $P;
        $sz = sin($z);
        $s2z = sin(2.0*$z);
        $s3z = sin(3.0*$z);
        $s4z = sin(4.0*$z);
        $s5z = sin(5.0*$z);
        $cz = cos($z);
        $c2z = cos(2.0*$z);
        $c3z = cos(3.0*$z);
        $c4z = cos(4.0*$z);
        $c5z = cos(5.0*$z);
        $G = (83.76922 + 218.4901*$T)*Meeus1x::PIs180;
        $cg = cos($G);
        $sg = sin($G);
        $H = 2.0*$G - $S;
        $sh = sin($H);
        $s2h = sin(2.0*$H);
        $ch = cos($H);
        $c2h = cos(2.0*$H);
        //
        return array($up, $up2, $P, $Q, $sq, $s2q, $s3q, $s4q, $cq, $c2q, $c3q, $c4q, $S, $V, $sv, $s2v,
                                 $cv, $c2v, $W, $sw, $z, $sz, $s2z, $s3z, $s4z, $s5z, $cz, $c2z, $c3z, $c4z, $c5z, $G,
                                 $cg, $sg, $H, $sh, $s2h, $ch, $c2h);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Jupiter - spherical coordinates. **/
    private function calcJupiter(){
        $T = $this->T;
        $T2 = $this->T2;
        $T3 = $this->T3;
        $mas = $this->mas;
        $M5 = $mas[4];
        list($up, $up2, $P, $Q, $sq, $s2q, $s3q, $s4q, $cq, $c2q, $c3q, $c4q, $S, $V, $sv, $s2v,
                 $cv, $c2v, $W, $sw, $z, $sz, $s2z, $s3z, $s4z, $s5z, $cz, $c2z, $c3z, $c4z, $c5z, $G,
                 $cg, $sg, $H, $sh, $s2h, $ch, $c2h) = $this->gaz;
        $LM = 238.049257 + (3036.301986 + (0.0003347 - 0.00000165*$T)*$T)*$T;
        $AA = (0.331364-0.010281*$up-0.004692*$up2)*$sv
        + (0.003228-0.064436*$up+0.002075*$up2)*$cv
        - (0.003083+0.000275*$up-0.000489*$up2)*$s2v
        + 0.002472*$sw
        + 0.013619*$sz
        + 0.018472*$s2z
        + 0.006717*$s3z
        + 0.002775*$s4z
        + (0.007275-0.001253*$up)*$sz*$sq
        + 0.006417*$s2z*$sq
        + 0.002439*$s3z*$sq
        - (0.033839+0.001125*$up)*$cz*$sq
        - 0.003767*$c2z*$sq
        - (0.035681+0.001208*$up)*$sz*$sq
        - 0.004261*$s2z*$cq
        + 0.002178*$cq
        + (-0.006333+0.001161*$up)*$cz*$cq
        - 0.006675*$c2z*$cq
        - 0.002664*$c3z*$cq
        - 0.002572*$sz*$s2q
        - 0.003567*$s2z*$s2q
        + 0.002094*$cz*$c2q
        + 0.003342*$c2z*$c2q;
        $LM = $LM + $AA;
        $LM = Meeus1x::mod360($LM);
        //
        $a = 5.202561
        + (-263.0*$cv
        + 205.0*$cz
        + 693.0*$c2z
        + 312.0*$c3z
        + 147.0*$c4z
        + 299.0*$sz*$sq
        + 181.0*$c2z*$sq
        + 204.0*$s2z*$cq
        + 111.0*$s3z*$cq
        - 337.0*$cz*$cq
        - 111.0*$c2z*$cq)*1.0E-06;
        //
        $ecc = 0.04833475 + (0.000164180 +(-4.676E-07 - 1.7E-09*$T)*$T)*$T;
        $ecc1 = (3606.0 + 130.0*$up - 43.0*$up2)*$sv
        + (1289.0 - 580.0*$up)*$cv
        - 6764.0*$sz*$sq
        - 1110.0*$s2z*$sq
        - 224.0*$s3z*$sq
        - 204.0*$sq
        + (1284.0 + 116.0*$up)*$cz*$sq
        + 188.0*$c2z*$sq
        + (1460.0 + 130.0*$up)*$sz*$cq
        + 224.0*$s2z*$cq
        - 817.0*$cq
        + 6074.0*$cz*$cq
        + 992.0*$c2z*$cq
        + 508.0*$c3z*$cq
        + 230.0*$c4z*$cq
        + 108.0*$c5z*$cq
        - (956.0 + 73.0*$up)*$sz*$s2q
        + 448.0*$s2z*$s2q
        + 137.0*$s3z*$s2q
        + (-997.0 + 108.0*$up)*$cz*$s2q
        + 480.0*$c2z*$s2q
        + 148.0*$c3z*$s2q
        + (-956.0 + 99.0*$up)*$sz*$c2q
        + 490.0*$s2z*$c2q
        + 158.0*$s3z*$c2q
        + 179.0*$c2q
        + (1024.0 + 75.0*$up)*$cz*$c2q
        - 437.0*$c2z*$c2q
        - 132.0*$c3z*$c2q;
        $ecc1 = $ecc1*1.0E-07;
        //
        $i = 1.308736 + (-0.0056961 + 0.0000039*$T)*$T;
        $i = Meeus1x::mod360($i);
        $om = 99.443414 + (1.01053 + (0.00035222 - 8.51E-06*$T)*$T)*$T;
        $om = Meeus1x::mod360($om);
        //
        $BB = (0.007192-0.003147*$up)*$sv
        + (-0.020428-0.000675*$up+0.000197*$up2)*$cv
        + (0.007269+0.000672*$up)*$sz*$sq
        - 0.004344*$sq
        + 0.034036*$cz*$sq
        + 0.005614*$c2z*$sq
        + 0.002964*$c3z*$sq
        + 0.037761*$sz*$cq
        + 0.006158*$s2z*$cq
        - 0.006603*$cz*$cq
        - 0.005356*$sz*$s2q
        + 0.002722*$s2z*$s2q
        + 0.004483*$cz*$s2q
        - 0.002642*$c2z*$s2q
        + 0.004403*$sz*$c2q
        - 0.002536*$s2z*$c2q
        + 0.005547*$cz*$c2q
        - 0.002689*$c2z*$c2q;
        //
        $M5 = $M5 + $AA - ($BB/$ecc);
        $ecc = $ecc + $ecc1;
        $E = Meeus1x::keplerEq($ecc,$M5*Meeus1x::PIs180); //$E in radians
        $nu = 2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2)))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        $r = $a*(1-$ecc*cos($E));
        $u = $LM + $nu - $M5 - $om;
        $u = Meeus1x::mod360($u);
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = Meeus1x::mod360($l);
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        return new Vector3($r, $l, $b);
    }


    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Saturn - spherical coordinates. **/
    private function calcSaturn(){
        $T = $this->T;
        $mas = $this->mas;
        list($up, $up2, $P, $Q, $sq, $s2q, $s3q, $s4q, $cq, $c2q, $c3q, $c4q, $S, $V, $sv, $s2v,
                 $cv, $c2v, $W, $sw, $z, $sz, $s2z, $s3z, $s4z, $s5z, $cz, $c2z, $c3z, $c4z, $c5z, $G,
                 $cg, $sg, $H, $sh, $s2h, $ch, $c2h) = $this->gaz;
        $M6 = $mas[5];
        $k = $S - $Q; // $k stands for psi
        $c2k = cos(2.0*$k);
        $c3k = cos(3.0*$k);
        $c4k = cos(4.0*$k);
        $s2k = sin(2.0*$k);
        $s3k = sin(3.0*$k);
        $s4k = sin(4.0*$k);
        //
        $LM = 266.564377 + (1223.509884 + (0.0003245 -5.8E-06*$T)*$T)*$T;
        //
        $AA = (-0.814181+0.018150*$up+0.016714*$up2)*$sv
        + (-0.010497+0.160906*$up-0.004100*$up2)*$cv
        + 0.007581*$s2v
        - 0.007986*$sw
        - 0.148811*$sz
        - 0.040786*$s2z
        - 0.015208*$s3z
        - 0.006339*$s4z
        - 0.006244*$sq
        + (0.008931-0.002728*$up)*$sz*$sq
        - 0.016500*$s2z*$sq
        - 0.005775*$s3z*$sq
        + (0.081344+0.003206*$up)*$cz*$sq
        + 0.015019*$c2z*$sq
        + (0.085581+0.002494*$up)*$sz*$cq
        + (0.025328-0.003117*$up)*$cz*$cq
        + 0.014394*$c2z*$cq
        + 0.006319*$c3z*$cq
        + 0.006369*$sz*$s2q
        + 0.009156*$s2z*$s2q
        + 0.007525*$s3k*$s2q
        - 0.005236*$cz*$c2q
        - 0.007736*$c2z*$c2q
        - 0.007528*$c3k*$c2q;
        //
        $LM = $LM + $AA;
        $LM = Meeus1x::mod360($LM);
        //
        $a = 9.554747
        + (572*$up*$sv
        + 2933*$cv
        + 33629*$cz
        - 3081*$c2z
        - 1423*$c3z
        - 671*$c4z
        - 320*$c5z
        + 1098*$sq
        - 2812*$sz*$sq
        + 688*$s2z*$sq
        - 393*$s3z*$sq
        - 228*$s4z*$sq
        + 2138*$cz*$sq
        - 999*$c2z*$sq
        - 642*$c3z*$sq
        - 325*$c4z*$sq
        - 890*$cq
        + 2206*$sz*$cq
        - 1590*$s2z*$cq
        - 647*$s3z*$cq
        - 344*$s4z*$cq
        + 2885*$cz*$cq
        + (2172+102*$up)*$c2z*$cq
        + 296*$c3z*$cq
        - 267*$s2z*$s2q
        - 778*$cz*$s2q
        + 495*$c2z*$s2q
        + 250*$c3z*$s2q
        - 856*$sz*$c2q
        + 441*$s2z*$c2q
        + 296*$c2z*$c2q
        + 211*$c3z*$c2q
        - 427*$sz*$s3q
        + 398*$s3z*$s3q
        + 344*$cz*$c3q
        - 427*$c3z*$c3q)*1.0E-06;
        //
        $ecc = 0.05589232+(-0.0003455+(-7.28E-07 + 7.4E-10*$T)*$T)*$T;
        //
        $ecc1 = (-7927+2548*$up+91*$up2)*$sv
        + (13381+1226*$up-253*$up2)*$cv
        + (248-121*$up)*$s2v
        - (305+91*$up)*$c2v
        + 412*$s2z
        + 12415*$sq
        + (390-617*$up)*$sz*$sq
        + (165-204*$up)*$s2z*$sq
        + 26599*$cz*$sq
        - 4687*$c2z*$sq
        - 1870*$c3z*$sq
        - 821*$c4z*$sq
        - 377*$c5z*$sq
        + 497*$c2k*$sq
        + (163-611*$up)*$cq
        - 12696*$sz*$cq
        - 4200*$s2z*$cq
        - 1503*$s3z*$cq
        - 619*$s4z*$cq
        - 268*$s5z*$cq
        - (282+1306*$up)*$cz*$cq
        + (-86+230*$up)*$c2z*$cq
        + 461*$s2k*$cq
        - 350*$s2q
        + (2211-286*$up)*$sz*$cq;
        $ecc1 = $ecc1 - 2208*$s2z*$s2q
        - 568*$s3z*$s2q
        - 346*$s4z*$s2q
        - (2780+222*$up)*$cz*$s2q
        + (2022+263*$up)*$c2z*$s2q
        + 248*$c3z*$s2q
        + 242*$s3k*$s2q
        + 467*$c3k*$s2q
        - 490*$c2q
        - (2842+279*$up)*$sz*$c2q
        + (128+226*$up)*$s2z*$c2q
        + 224*$s3z*$c2q
        + (-1594+282*$up)*$cz*$c2q
        + (2162-207*$up)*$c2z*$c2q
        + 561*$c3z*$c2q
        + 343*$c4z*$c2q
        + 469*$s3k*$c2q
        - 242*$c3k*$c2q
        - 205*$sz*$s3q
        + 262*$s3z*$s3q
        + 208*$cz*$c3q
        - 271*$c3z*$c3q
        - 382*$c3z*$s4q
        - 376*$s3z*$c4q;
        $ecc1 = $ecc1*1.0E-07;
        //
        $om = 112.790414 + (0.8731951 +(-0.00015218 - 5.31E-06*$T)*$T)*$T;
        $i = 2.492519 +(-0.0039189 +(-0.00001549 + 4.0E-08*$T)*$T)*$T;
        //
        $BB = (0.077108 + 0.007186*$up - 0.001533*$up2)*$sv
        + (0.045803 - 0.014766*$up - 0.000536*$up2)*$cv
        - 0.007075*$sz
        - 0.075825*$sz*$sq
        - 0.024839*$s2z*$sq
        - 0.008631*$s3z*$sq
        - 0.072586*$cq
        - 0.150383*$cz*$cq
        + 0.026897*$c2z*$cq
        + 0.010053*$c3z*$cq
        - (0.013597 + 0.001719*$up)*$sz*$s2q
        + (-0.007742 + 0.001517*$up)*$cz*$s2q
        + (0.013586 - 0.001375*$up)*$c2z*$s2q
        + (-0.013667 + 0.001239*$up)*$sz*$c2q
        + 0.011981*$s2z*$c2q
        + (0.014861 + 0.001136*$up)*$cz*$c2q
        - (0.013064 + 0.001628*$up)*$c2z*$c2q;
        //
        $M6 = $M6 + $AA - ($BB/$ecc);
        $ecc = $ecc + $ecc1;
        $E = Meeus1x::keplerEq($ecc,$M6*Meeus1x::PIs180); //$E in radians
        $nu = 2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2)))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        $r = $a*(1-$ecc*cos($E));
        $u = $LM + $nu - $M6 - $om;
        $u = Meeus1x::mod360($u);
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = Meeus1x::mod360($l);
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        $b = $b + 0.000747*$cz*$sq
                 + 0.001069*$cz*$cq
                 + 0.002108*$s2z*$s2q
                 + 0.001261*$c2z*$s2q
                 + 0.001236*$s2z*$c2q
                 - 0.002075*$c2z*$c2q;
        $b = Meeus1x::mod360($b);
        return new Vector3($r, $l, $b);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Uranus - spherical coordinates. **/
    private function calcUranus(){
        $T = $this->T;
        $mas = $this->mas;
        $M7 = $mas[6];
        list($up, $up2, $P, $Q, $sq, $s2q, $s3q, $s4q, $cq, $c2q, $c3q, $c4q, $S, $V, $sv, $s2v,
                 $cv, $c2v, $W, $sw, $z, $sz, $s2z, $s3z, $s4z, $s5z, $cz, $c2z, $c3z, $c4z, $c5z, $G,
                 $cg, $sg, $H, $sh, $s2h, $ch, $c2h) = $this->gaz;
        // $TO=tau, $ET=eta, $TE=teta.
        $TO = $S - $P;
        $ET = $S - $Q;
        $TE = $G - $S;
        //
        $LM = 244.19747 + (429.863546+ (0.0003160 - 6.0E-07*$T)*$T)*$T;
        $AA = (0.864319-0.001583*$up)*$sh
        +(0.082222-0.006833*$up)*$ch
        +0.036017*$s2h
        -0.003019*$c2h
        +0.008122*$sw;
        $LM = $LM + $AA;
        $LM = Meeus1x::mod360($LM);
        //
        $BB = 0.120303*$sh
        + (0.019472-0.000947*$up)*$ch
        + 0.006197*$s2h;
        //
        $a = 19.21814 - 0.003825*$ch;
        //
        $ecc = 0.0463444 +(-0.00002658 + 7.7E-08*$T)*$T;
        $ecc1 = ((-3349+163*$up)*$sh
        + 20981*$ch
        + 1311*$c2h)*1.0E-07;
        //
        $om = 73.477111 + (0.4986678 + 0.0013117*$T)*$T;
        $i = 0.772464 + (0.0006253 + 0.0000395*$T)*$T;
        //
        $M7 = $M7 + $AA - ($BB/$ecc);
        $ecc = $ecc + $ecc1;
        //
        $E = Meeus1x::keplerEq($ecc,$M7*Meeus1x::PIs180); //$E in radians
        //
        $nu = 2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2)))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        //
        $r = $a*(1-$ecc*cos($E)) +
        (
        - 25948.0 + (5795*cos($S)-1165*sin($S)+1388*cos(2.0*$S))*sin($ET)
        + 4985*$cz+(1351*cos($S)+5702*sin($S)+1388*sin(2.0*$S))*cos($ET)
        - 1230*cos($S)+904*cos(2.0*$TE)
        + 3354*cos($ET)+894*(cos($TE)-cos(3.0*$TE))
        )*1.0E-06;
        $u = $LM + $nu - $M7 - $om;
        $u = Meeus1x::mod360($u);
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = $l + (0.010122-0.000988*$up)*sin($S+$ET)
        + (-0.038581+0.002031*$up-0.001910*$up2)*cos($S+$ET)
        + (0.034964-0.001038*$up+0.000868*$up2)*cos(2.0*$S+$ET)
        + 0.005594*sin($S+3.0*$TE)
        - 0.014808*$sz
        - 0.005794*sin($ET)
        + 0.002347*cos($ET)
        + 0.009872*sin($TE)
        + 0.008803*sin(2.0*$TE)
        - 0.004308*sin(3.0*$TE);
        $l = Meeus1x::mod360($l);
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        $b = $b + (0.000458*sin($ET)-0.000642*cos($ET)-0.000517*cos(4.0*$TE))*sin($S)
        -(0.000347*sin($ET)+0.000853*cos($ET)+0.000517*cos(4.0*$ET))*cos($S)
        +0.000403*(cos(2.0*$TE)*sin(2.0*$S)+sin(2.0*$TE)*cos(2.0*$S));
        $b = Meeus1x::mod360($b);
        return new Vector3($r, $l, $b);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Neptune - spherical coordinates. **/
    private function calcNeptune(){
        $T = $this->T;
        $mas = $this->mas;
        $M8 = $mas[7];
        list($up, $up2, $P, $Q, $sq, $s2q, $s3q, $s4q, $cq, $c2q, $c3q, $c4q, $S, $V, $sv, $s2v,
                 $cv, $c2v, $W, $sw, $z, $sz, $s2z, $s3z, $s4z, $s5z, $cz, $c2z, $c3z, $c4z, $c5z, $G,
                 $cg, $sg, $H, $sh, $s2h, $ch, $c2h) = $this->gaz;
        // $TO=tau, $ET=eta, $TE=teta.
        $TO = $G - $P;
        $ET = $G - $Q;
        $TE = $G - $S;
        //
        $LM = 84.457994 + (219.885914 + (0.0003205 - 6.0E-07*$T)*$T)*$T;
        $AA = (-0.589833+0.001089*$up)*$sh
        + (-0.056094+0.004658*$up)*$ch
        - 0.024286*$s2h;
        $LM = $LM + $AA;
        $LM = Meeus1x::mod360($LM);
        //
        $BB = 0.024039*$sh
        - 0.025303*$ch
        + 0.006206*$s2h
        - 0.005992*$c2h;
        //
        $a = 30.10957+(-817*$sh+8189*$ch+781*$c2h)*1.0E-06;
        //
        $ecc = 0.00899704 + (6.33E-06 - 2.0E-09*$T)*$T;
        $ecc1 = (4389*$sh
        + 4262*$ch
        + 1129*$s2h
        + 1089*$c2h)*1.0E-07;
        //
        $om = 130.681389 + (1.098935 + (0.00024987 - 4.718E-06*$T)*$T)*$T;
        //
        $i = 1.779242 + (-0.0095436 - 9.1E-06*$T)*$T;
        //
        $M8 = $M8 + $AA - ($BB/$ecc);
        $ecc = $ecc + $ecc1;
        //
        $E = Meeus1x::keplerEq($ecc,$M8*Meeus1x::PIs180); //$E in radians
        //
        $nu = 2*atan(sqrt((1+$ecc)/(1-$ecc))*(tan($E/2)))/Meeus1x::PIs180;
        $nu = Meeus1x::mod360($nu);
        //
        $r = $a*(1-$ecc*cos($E)) +
        ( 40596
        + 4992*$cz
        + 2744*cos($ET)
        + 2044*cos($TE)
        + 1051*cos(2*$TE))*1.0E-06;
        //
        $u = $LM + $nu - $M8 - $om;
        $u = Meeus1x::mod360($u);
        //
        $l = atan2(cos($i*Meeus1x::PIs180)*sin($u*Meeus1x::PIs180),cos($u*Meeus1x::PIs180))/Meeus1x::PIs180 + $om;
        $l = $l - 0.009556*$sz
        - 0.005178*sin($ET)
        + 0.002572*sin(2*$TE)
        - 0.002972*cos(2*$TE)*$sg
        - 0.002833*sin(2*$TE)*$cg;
        $l = Meeus1x::mod360($l);
        //
        $b = asin(sin($u*Meeus1x::PIs180)*sin($i*Meeus1x::PIs180))/Meeus1x::PIs180;
        $b = $b + 0.000336*cos(2*$TE)*$sg
                 + 0.000364*sin(2*$TE)*$cg;
        $b = Meeus1x::mod360($b);
        return new Vector3($r, $l, $b);
    }
    
    
    //***************************************************
    /** Computes the heliocentric ecliptic coordinates of Pluto - spherical coordinates. **/
    private function calcPluto(){
        $T = $this->T;
        $xs = - $this->geomEarth_cart->x1;
        $ys = - $this->geomEarth_cart->x2;
//        $xs = GCpol[2].x1*cos(GCpol[2].x2*Meeus1x::PIs180);
//        $ys = GCpol[2].x1*sin(GCpol[2].x2*Meeus1x::PIs180);
        $n_p = 36525*$T - 364.5;
        //            di=8.2;
        $l0 = 1.6406;
        $lp = 701214E-10;
        $p0 = 3.8978;
        $pp = 6.672E-7;
        $o0 = 1.9034;
        $op = 66.72E-08;
        $e = 0.250236;
        $i1 = 0.29968;
        $a = 39.438712;
        $p = $p0+$pp*$n_p;
        //            px = p*180.0/M_PI;
        $l1 = $l0+$lp*$n_p;
        //            lx = l1*180.0/M_PI;
        $m = $l1-$p;
        $u = Meeus1x::keplerEq($e,$m);
        $v = 2*atan(sqrt((1+$e)/(1-$e))*(tan($u/2)));
        $o = $o0 + $op*$n_p;
        $c = $v + $p - $o;
        if(cos($c)==0) $d = $c;
        else $d = atan2(sin($c)*cos($i1), cos($c));
        $ls = $d+$o;
        $bs = atan2(sin($d)*sin($i1),cos($i1));
        $rs = $a*(1-$e*cos($u));
        $xp = $rs*cos($bs)*cos($ls)+$xs;
        $yp = $rs*cos($bs)*sin($ls)+$ys;
        $zp = $rs*sin($bs);
        //
        $r=sqrt($xp*$xp+$yp*$yp);
        $l=atan2($yp,$xp)/Meeus1x::PIs180;
        $b=atan2($zp,$r)/Meeus1x::PIs180;
        //
        return new Vector3($r, $l, $b);
    }
    
    /**
        Computes the geocentric ecliptic longitude of mean moon north node
        Result in decimal degrees
        cf chap 30 of Meeus.
        Formula also implemented in calcMoon()
    **/
    private function calcMeanLunarNode(){
        return Meeus1x::mod360(259.183275 - 1934.142 * $this->T + 0.002078 * $this->T2 - 0.0000022*$this->T3);
    }
} // end class


//******************************************************************************************************
//                                          Auxiliary code
//******************************************************************************************************

/** 
    Auxiliary functions for Meeus1
**/
class Meeus1x {
    
    /** PI / 180 **/
    const PIs180 = 0.017453292519943295769236907684886;
    
    
    /** Computation of the eccentric anomaly from eccentricity and mean anomaly.
        @param e Eccentricity.
        @param M Mean anomaly.
        @return Eccentric anomaly.
    **/
    public static function keplerEq($e, $M){
        $ct = 0;
        $u_p = 0;
        $u = $M;
        $precision = 0.0000000000001;
        while (abs($u - $u_p) > $precision){
            $u_p = $u;
            $u = $M + $e * sin($u);
            $ct++;
            if ($ct > 25){
                $precision = $precision * 2;
                $ct = 0;
            }
        }
        return $u;
    }
    
    //********************* mod360 ******************************
    /** Returns a number between 0 and 360. */
    public static function mod360($nb){
        while ($nb > 360.0) $nb -= 360.0;
        while ($nb < 0.0) $nb += 360.0;
        return $nb;
    }
    
    //********************* sphereToCart ******************************
    /** Transform a Vector3 from spherical to cartesian expression.
    @param $v A Vector3, with the angles expressed in degrees.
    @return The cartesian coordinates ; the unit is the same as the distance unit of $v.
    */
    public static function sphereToCart(Vector3 $v){
        $r = $v->x1;
        $theta = deg2rad($v->x2);
        $phi = deg2rad($v->x3);
        $x = $r*cos($phi)*cos($theta);
        $y = $r*cos($phi)*sin($theta);
        $z = $r*sin($phi);
        return new Vector3($x, $y, $z);
    }
    
    //********************* cartTophere ******************************
    /** Transform a Vector3 from    cartesian to spherical expression.
    @param $v A Vector3, with the angles expressed in degrees.
    @return The spherical coordinates ; the angles are in degrees.
    */
    public static function cartToSphere(Vector3 $v){
        // variables to remember initial values.
        $X = $v->x1;
        $Y = $v->x2;
        $Z = $v->x3;
        $rho2 = $X*$X + $Y*$Y + $Z*$Z;
        $rho = sqrt($rho2);
        $theta = Meeus1x::atan3($Y, $X);
        $phi = asin($Z / $rho);
        return new Vector3($rho, $theta, $phi);
    }
    
    //*************************** atan3 ***********************************
    /** Computation of arcTangent, giving a result in [0, 2*<FONT FACE="symbol">p</FONT>[.
    @return A number <CODE>alpha</CODE> such as <CODE>cos(alpha) = x/sqrt(x*x + y*y)</CODE> and 
    <CODE>sin(alpha) = y/sqrt(x*x + y*y)</CODE> and alpha belongs to [0, 2*<FONT FACE="symbol">p</FONT>[.
    */
    public static function atan3($y, $x){
        $alpha = atan2($y, $x); // belongs to [-PI, PI[
        if ($alpha >= 0){
            return $alpha;
        }
        else { 
            return $alpha + 2*M_PI;
        }
    }

} // end class

//*********************************************************************
// Vector3
//*********************************************************************
/** 
    Point in a 3D space
**/
class Vector3 {
    var $x1, $x2, $x3;

    function __construct($x1, $x2, $x3){
        $this->x1 = $x1; $this->x2 = $x2; $this->x3 = $x3;
    }

    /** Norm of a vector. **/
    public function norm(){
        return sqrt($this->x1*$this->x1
                  + $this->x2*$this->x2
                  + $this->x3*$this->x3);
    }

    /** 
        Substraction of vectors. 
        @return $v1 - $v2.
    **/
    public static function sub($v1, $v2){
        return new Vector3(
            $v1->x1 - $v2->x1,
            $v1->x2 - $v2->x2,
            $v1->x3 - $v2->x3
        );
    }
    
} // end class
