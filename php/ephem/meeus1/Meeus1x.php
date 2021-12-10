<?php 
/********************************************************************************
    Auxiliary functions for Meeus1
    
    @license    GPL
    @history    2021-04-03 02:15:01+01:00, Thierry Graff : Extract from Meeus1.php
****************************************************************************************/
namespace tigeph\ephem\meeus1;

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
