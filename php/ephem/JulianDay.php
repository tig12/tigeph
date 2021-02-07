<?php
/********************************************************************************
    Julian day computation
    Note : for astronomical computations, don't use php function gregoriantojd(),
           not precise enough (it returns an integer in PHP 8).
    
    @licence    GPL
    @history    2021-02-05 17:58:06+01:00, Thierry Graff : Integration to tigeph
    @history    2011-02-06T08:18:13+01:00, Thierry Graff : Creation from a split of class Date
****************************************************************************************/
namespace tigeph\ephem;

class JulianDay{
    
    //**********************************************
    /**
        Calls {@link date2jd()} for a string containing an ISO 8601 date
        No check on $str
        @param $str ISO 8601 date that must be expressed in UTC 
    **/
    public static function isoDate2jd($str){
        $dt = new \DateTime($str);
        return JulianDay::date2jd(
            $dt->format('Y'),
            $dt->format('m'),
            $dt->format('d'),
            $dt->format('H'),
            $dt->format('i'),
            $dt->format('s')
        );
    }
    
    
    //**********************************************
    /**
        Calculates the number of julian days elapsed since jan 0.5 4712 BC.
        Source : Jean Meeus, "Astronomical Algorithms", 2nd edition, p 61
    **/
    public static function date2jd($year, $month, $day, $hour, $minute, $second){
        $decDay = $day + $hour/24 + $minute/1440 + $second/86400;
        if($month>2){
            $y1 = $year;
            $m1 = $month;
        }
        else{
            $y1 = $year-1;
            $m1 = $month+12;
        }
        if(($year+$month/100.0 + $decDay/10000.0) >= 1582.1015){
            $a = floor($y1/100.0);
            $b = 2-$a+floor($a/4.0);
        }
        else{
            $b = 0;
        }
        $jd = floor(365.25*($y1+4716)) + floor(30.6001*($m1+1)) + $decDay + $b - 1524.5;
        return $jd;
    }
    
    
    //**********************************************
    /**
        Computes a date from a julian day (the number of julian days elapsed since jan 0.5 4712 BC).
        Source : Jean Meeus, "Astronomical Algorithms", 2nd edition, chap. 7.
        @param $jd The julian day.
        @return An array containing 6 elements : years, months, days, hours, minutes, decimal seconds.
    **/
    public static function jd2date($jd){
        $DAYS_PER_YEAR = 365.25;
        if($jd < 0)
            throw new \Exception("jd2date() not valid for negative julian days");
        $z = floor($jd + 0.5);
        $f = $jd + 0.5 - $z;
        if($z < 2299161.0){
            $a = $z;
        }
        else{
            $alpha = floor(($z - 1867216.25) / 36524.25);
            $a = $z + 1 + $alpha - floor($alpha/4.0);
        }
        $b = $a + 1524.0;
        $c = floor(($b - 122.1) / $DAYS_PER_YEAR);
        $d = floor($DAYS_PER_YEAR * $c);
        $e = floor(($b - $d) / 30.6001);
        // months
        if($e < 14.0){
            $res[1] = $e - 1;
        }
        else{
            $res[1] = $e - 13;
        }
        // years
        if($res[1] > 2){
            $res[0] = $c - 4716;
        }
        else{
            $res[0] = $c - 4715;
        }
        // days
        $res[2] = $b - $d - floor(30.6001 * $e);
        // hours
        $hours = 24 * $f; // decimal hours
        $res[3] = floor($hours);
        // minuts
        $minutes = ($hours - floor($hours)) * 60.0; // decimal minuts
        $res[4] = floor($minutes);
        // seconds
        $secs = ($minutes - floor($minutes)) * 60.0; // decimal seconds
        $res[5] = floor(1000 * $secs) / 1000; // round to 1/1000 second
        return $res;
    }
    
    
}// end class
