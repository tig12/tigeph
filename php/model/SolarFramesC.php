<?php
/********************************************************************************
    Constants for reference frames used in the solar system.
    
    @license    GPL
    @history    2021-02-03 00:43:04+01:00, Thierry Graff : integration to tigeph
    @history    2007.02.26               , Thierry Graff : php5 port from jephem.astro.spacetime.SpaceConstants.java
    @history    2001                     , Thierry Graff : Creation from a part of jephem.astro.Body.java.
****************************************************************************************/
namespace tigeph\model;

class SolarFramesC {

    /**
        Constant indicating that a coordinate is expressed in the frame
        of the theory used to compute it.
    **/
    const THEORY = 0;
    
    /**
        Constant designating heliocentric ecliptic reference frame,
        for geometric coordinates.
    **/
    const EC_HELIO_GEOMETRIC = 1;
    const HELIO = 1;
    
    /**
        Constant designating geocentric ecliptic reference frame ;
        coordinates expressed in this frame are <b>true apparent coordinates</b>.
        Reference system : FK5.
        Reference plane = mean ecliptic of date.
        Equinox = true equinox of date.
    **/
    const ECLIPTIC = 2;
    const EC = 2;
    
    /**
        Constant designating geocentric equatorial reference frame ;
        coordinates expressed in this frame are <b>true apparent coordinates</b>.
        Reference system : FK5.
        Reference plane = true equator of date.
        Equinox = true equinox of date.
    **/
    const EQUATORIAL = 3;
    const EQ = 3;
    
    /** Constant designating equatorial topocentric reference frame. **/
    //  const EQUATORIAL_TOPOCENTRIC = 4;
    
    /** Constant designating horizontal topocentric reference frame. **/
    const HORIZONTAL_TOPOCENTRIC = 5;
    
    /** Constant designating galactic reference frame. **/
    const GALACTIC = 6;

} // end class
