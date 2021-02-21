<?php
/******************************************************************************
    
    Interface that must be satisfied by classes implementing ephemeris computation

    @license    GPL
    @history    2021-02-02 16:06:03+01:00, Thierry Graff : Creation
********************************************************************************/
namespace tigeph;

interface Ephem {
    
    /** 
        Computation of astronomical or astrological points.
        
        @param $date    ISO 8601 date, format "YYYY-MM-DD HH:MM:SS"
        @param $what    Array of things to compute (planets, houses), using constants of tigeph\model
        @param $params  Optional parameters, depending on the implementing class
        
        @return associative array
            map planet code => planet geocentric ecliptic longitude, in decimal degrees
    **/
    public static function ephem(
        $date,
        $what,
        $params=[],
    );
    
    /** 
        Returns an array of "things" (astrological points) handled by the implementing class, using constants of tigeph\model.
    **/
    public static function getComputableThings();
    
    
} // end interface
