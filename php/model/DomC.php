<?php 
/****************************************************************************************
    Constants for domification (astrological houses)
    
    @license    GPL
    @history    2021-02-03 00:46:13+01:00, Thierry Graff : Integration to tigeph
    @history    2008-05-20 06:24         , Thierry Graff : Converted functions to a class
    @history    2002-11-25               , Thierry Graff : First attempt to write placidius()
    @history    2002-11-10 :             , Thierry Graff : Creation
****************************************************************************************/
namespace tigeph\model;

class DomC{
    
    /** 
        International Astrological Abbreviations for houses,
        as found in journal "Correlation" (vol 30.2 2016)
    **/
    const IAA = [
        DomC::ASC   => 'AS',
        DomC::DESC  => 'DS',
        DomC::MH    => 'MC',
        DomC::IC    => 'IC',
    ];
    
    //*********************************************************
    //                Domification systems
    //*********************************************************
    /** Constant designating the Placidus domification system. **/
    const PLACIDUS        = 'placidus';
    /** Constant designating the Koch domification system. **/
    const KOCH            = 'koch';
    /** Constant designating the Porphyrius domification system. **/
    const PORPHYRIUS      = 'porphyrius';
    /** Constant designating the Regiomontanus domification system. **/
    const REGIOMONTANUS   = 'regiomontanus';
    /** Constant designating the Regiomontanus domification system. **/
    const CAMPANUS        = 'campanus';
    /** Constant designating the Aequalis (equal houses) domification system. **/
    const AEQUELIS        = 'aequalis';
    /** Constant designating the "Whole sign" domification system. **/
    const WHOLE_SIGN      = 'whole-sign';
    
    
    //*********************************************************
    //                House cuspides
    //*********************************************************
    //
    // The values are used as 0-based array indexes
    //
    /** Constant designating cuspide of first house (synonym of {@link H1}). **/
    const ASC = 'H1';
    /** Constant designating cuspide of fourth house (Imum Coeli, synonym of {@link H4}). **/
    const IC = 'H4';
    /** Constant designating cuspide of seventh house (synonym of {@link H7}). **/
    const DESC = 'H7';
    /** Constant designating cuspide of tenth house (Mid Heaven, synonym of {@link H10}). **/
    const MH = 'H10';
    
    /** Constant designating cuspide of first house (synonym of {@link ASC}). **/
    const H1 = 'H1';
    /** Constant designating cuspide of second house. **/
    const H2 = 'H2';
    /** Constant designating cuspide of third house. **/
    const H3 = 'H3';
    /** Constant designating cuspide of fourth house (synonym of {@link IC}). **/
    const H4 = 'H4';
    /** Constant designating cuspide of fifth house. **/
    const H5 = 'H5';
    /** Constant designating cuspide of sixth house. **/
    const H6 = 'H6';
    /** Constant designating cuspide of seventh house (synonym of {@link DESC}). **/
    const H7 = 'H7';
    /** Constant designating cuspide of eighth house. **/
    const H8 = 'H8';
    /** Constant designating cuspide of ninth house. **/
    const H9 = 'H9';
    /** Constant designating cuspide of tenth house. **/
    const H10 = 'H10';
    /** Constant designating cuspide of eleventh house (synonym of {@link MC}). **/
    const H11 = 'H11';
    /** Constant designating cuspide of twelvth house. **/
    const H12 = 'H12';
    
    /** Array containing the codes of all houses **/
    const ALL_HOUSES = [
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
    
    /**
    Array containing the labels of houses
    @todo language dependant, move with i18n code
    **/
    const HOUSE_LABELS = [
        DomC::H1    => 'ASC',
        DomC::H2    => 'M2',
        DomC::H3    => 'M3',
        DomC::H4    => 'FC',
        DomC::H5    => 'M5',
        DomC::H6    => 'M6',
        DomC::H7    => 'DESC',
        DomC::H8    => 'M8',
        DomC::H9    => 'M9',
        DomC::H10   => 'MC',
        DomC::H11   => 'M11',
        DomC::H12   => 'M12',
    ];
    
    
} // end class
