<?php
/******************************************************************************
    
    Provides informations about available computations

    @license    GPL
    @history    2021-02-20 23:04:49+01:00, Thierry Graff : Creation
********************************************************************************/
namespace tigeph;

class Tigeph {
    
    const ENGINES = [
        'meeus1',
        'swetest',
    ];
    
    public static function getEngines(){
        return self::ENGINES;
    }
    
}// end class
