<?php
/******************************************************************************
    Builds a version of tigeph to include in other softwares
    
    @license  GPL
    @history  2021-02-08 16:14:47+01:00, Thierry Graff : Creation
********************************************************************************/

namespace buildeph\pack;

class pack {
    
    /** 
        Main function, called by CLI
    **/
    public static function execute($params=[]){
        $rootDir = $outputDir = dirname(dirname(__DIR__));
        $destDir = $rootDir . DS . 'tmp' . DS . 'tigeph';
        if(is_dir($destDir)){
            self::rrmdir($destDir);
        }
        mkdir($destDir, 0755, true);
        // directories to create in $destDir
        $dirs = [
            $destDir . DS . 'php',
            $destDir . DS . 'php' . DS . 'model',
            $destDir . DS . 'php' . DS . 'ephem',
            $destDir . DS . 'php' . DS . 'ephem' . DS . 'meeus1',
            $destDir . DS . 'php' . DS . 'ephem' . DS . 'swetest',
        ];
        // files to copy to $destDir, relative to $rooDir
        // The same hierarchy is reproduced in $destDir 
        $files = [
            'config.yml.dist',
            'php' . DS . 'autoload.php',
            'php' . DS . 'Tigeph.php',
            'php' . DS . 'Ephem.php',
            'php' . DS . 'model' . DS . 'DomC.php',
            'php' . DS . 'model' . DS . 'IAA.php',
            'php' . DS . 'model' . DS . 'SolarFramesC.php',
            'php' . DS . 'model' . DS . 'SpaceTimeC.php',
            'php' . DS . 'model' . DS . 'SysolC.php',
            'php' . DS . 'ephem' . DS . 'meeus1' . DS . 'Meeus1.php',
            'php' . DS . 'ephem' . DS . 'swetest' . DS . 'Swetest.php',
        ];
        //
        foreach($dirs as $dir){
            mkdir($dir, 0755, true);
        }
        foreach($files as $file){
            copy($rootDir . DS . $file, $destDir . DS . $file);
        }
        $README = "Generated from tigeph\n"
            . "See https://github.com/tig12/tigeph\n";
        file_put_contents($destDir . DS . 'README', $README);
        //
        echo "Generated distributable version in $destDir\n";
    }
    
    /** 
        Recursively delete a directory
        Adaptation of example code found at
        https://www.php.net/manual/en/function.rmdir.php
    **/
    public static function rrmdir($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::rrmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
    
    
} // end class
