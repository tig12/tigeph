<?php
/****************************************************************************************
    Constants related to space and time
    
    @license    GPL
    @history    2021-02-02 17:06:48+01:00, Thierry Graff : Integration to tigeph
    @history    2010-11-16T23:50:40+01:00, Thierry Graff Creation from a merge of SpaceConstants and TimeConstants
    @history    2007.02.26               , Thierry Graff : PHP5 port from java : jephem.astro.spacetime.SpaceConstants.java
    @history    2001                     , Thierry Graff: Creation
****************************************************************************************/
namespace tigeph\model;

class SpaceTimeC {
  
    //****************************************************
    //                    spacetime
    //****************************************************
    
    /** Value of light velocity, (value UAI 1976 : 299792458 km.s<sup>-1</sup>). **/
    const LIGHT_VELOCITY = 299792458;
    
    
    //****************************************************
    //                    Time
    //****************************************************
    
    /** Julian date of 01/01/1900, 12h00m00s TU ( = 2415020.0). **/
    const JD1900 = 2415020.0;
    /** Julian date of 01/01/2000, 12h00m00s TU ( = 2451545.0). **/
    const JD2000 = 2451545.0;
    /** Julian date of 01/01/2100, 12h00m00s TU ( = 2488070.0). **/
    const JD2100 = 2488070.0;
    
    
    //****************************************************
    //                    Space
    //****************************************************
  
    // Constants to desingate spherical / cartesian.
    /** Constant designating the cartesian way to express coordinates. **/
    const CARTESIAN = 0;
    /** Constant designating the spherical way to express coordinates. **/
    const SPHERICAL = 1;
    /** Value of an astronomical unit, in km (IERS 1992). **/
    const KM_PER_AU = 149597870.61;

    //****************************************************
    //                    Space
    //****************************************************
  
    /** Constant used to characterize that a date is expressed in UTC (Universal Coordinated Time). */
    const UTC = 0;
    /** Constant used to characterize that a date is expressed in TT (Terrestrial Time), which
    is considered as equal to TDB (Temps Dynamique Barycentrique) in JEphem. */
    const TT_TDB = 1;
    
    /** Number of seconds in a day (24 x 3600 = 86400). **/
    const SECONDS_PER_DAY = 86400.0;
    /** Number of days per millenium ( = 365250). **/
    const DAYS_PER_MILLENIUM	= 365250.0;
    /** Number of days per century ( = 36525). **/
    const DAYS_PER_CENTURY	= 36525.0;
    /** Number of days per year ( = 365.25). **/
    const DAYS_PER_YEAR	= 365.25;

} // end class

