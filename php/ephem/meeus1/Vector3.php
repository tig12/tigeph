<?php 
/********************************************************************************
    Point in a 3D space
    
    @license    GPL
    @history    2021-04-03 02:11:51+01:00, Thierry Graff : Exctract from Meeus1.php
****************************************************************************************/
namespace tigeph\ephem\meeus1;

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
