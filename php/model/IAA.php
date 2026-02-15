<?php
/******************************************************************************
    International Astrological Abbreviations
    as found in journal "Correlation" (vol 30.2 2016)
    
    @license    GPL
    @history    2020-12-31 02:01:06+01:00, Thierry Graff : Creation
********************************************************************************/
namespace tigeph\model;

class IAA {
    
    /** List of all planet codes supported by IAA **/
    const PLANETS = ['SO', 'MO', 'ME', 'VE', 'MA', 'JU', 'SA', 'UR', 'NE', 'PL', 'NN', 'CH'];
    
    const PLANET_NAMES = [
        'SO' => 'Sun',
        'MO' => 'Moon',
        'ME' => 'Mercury',
        'VE' => 'Venus',
        'MA' => 'Mars',
        'JU' => 'Jupiter',
        'SA' => 'Saturn',
        'UR' => 'Uranus',
        'NE' => 'Neptune',
        'PL' => 'Pluto',
        'NN' => 'North node',
        'CH' => 'Chiron',
    ];
    
    
    /**  Match between constants used by tigeph and IAA for planets **/
    const TIGEPH_IAA = [
        SysolC::SUN               => 'SO',
        SysolC::MOON              => 'MO',
        SysolC::MERCURY           => 'ME',
        SysolC::VENUS             => 'VE',
        SysolC::MARS              => 'MA',
        SysolC::JUPITER           => 'JU',
        SysolC::SATURN            => 'SA',
        SysolC::URANUS            => 'UR',
        SysolC::NEPTUNE           => 'NE',
        SysolC::PLUTO             => 'PL',
        SysolC::MEAN_LUNAR_NODE   => 'NN',
        SysolC::CHIRON            => 'CH',
    ];

    const IAA_TIGEPH = [
        'SO' => SysolC::SUN,
        'MO' => SysolC::MOON,
        'ME' => SysolC::MERCURY,
        'VE' => SysolC::VENUS,
        'MA' => SysolC::MARS,
        'JU' => SysolC::JUPITER,
        'SA' => SysolC::SATURN,
        'UR' => SysolC::URANUS,
        'NE' => SysolC::NEPTUNE,
        'PL' => SysolC::PLUTO, 
        'NN' => SysolC::MEAN_LUNAR_NODE,
        'CH' => SysolC::CHIRON,
    ];
    
    /** 
        Checks that an array contains valid IAA codes.
        @return     A report indicating the invalid codes.
                    Empty string if all codes are valid.
    **/
    public static function checkCodes(array $codes): string{
        $invalids = [];
        foreach($codes as $code){
            if(!in_array($code, self::PLANETS)){
                $invalids[] = $code;
            }
        }
        if(count($invalids) == 0){
            return '';
        }
        return "INVALID CODE(S): '" . implode("', '", $invalids) . "'";
    }

} // end class
