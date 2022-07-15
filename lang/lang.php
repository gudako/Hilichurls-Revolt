<?php
require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";
use local\ConfigSystem;

function getlang(){
    if(!isset($_COOKIE['lang'])) return 'en';
    $lang_cookie = $_COOKIE['lang'];
    $config=new ConfigSystem();
    $lang_set = $config->GetAllLanguages();
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

function memtxt(){
    $config = new ConfigSystem();
    $shmop = shmop_open($config->GetShmopIdLang(), 'a', 0600, $config->GetShmopLangMaxsz());

    $args= func_get_args();
    if(count($args) == 1){
        $textcode = $args[0];
        if(gettype($textcode)==='array'){
            if(count($textcode)!==2||gettype($textcode[0])!=='integer'||gettype($textcode[1])!=='integer')
                throw new Exception("Parameter invalid: \"memtxt\" when called with invalid array");
            return memtxt($textcode[0], $textcode[1]);
        }
        if(gettype($textcode)!=='string')
            throw new Exception("Parameter invalid: \"memtxt\" called with unrecognized type");

        $dirEnd =hexdec(bin2hex(shmop_read($shmop, 0, 2)));
        $dirSz = $dirEnd-4;
        $hashSz = hexdec(bin2hex(shmop_read($shmop, 2, 1)));
        $offsetSz = hexdec(bin2hex(shmop_read($shmop, 3, 1)));
        $tupleSz = $hashSz + $offsetSz*2;

        $hash = substr(sha1($textcode),0,$hashSz*2);
        $primeIndex = (hexdec($hash)% ($dirSz/$tupleSz))*$tupleSz;
        $initIndex = $primeIndex+4;
        while($hash!== bin2hex(shmop_read($shmop,$primeIndex+4,$hashSz))){
            $primeIndex+=$tupleSz;
            if($primeIndex>=$dirSz)$primeIndex-=$dirSz;
            if($primeIndex==$initIndex)
                throw new Exception("Unable to find any text with the textcode \"$textcode\".");
        }

        $offset = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz+4,$offsetSz)));
        $size = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz+4+$offsetSz,$offsetSz)));
        return memtxt($offset, $size);
    }
    elseif(count($args) == 2){
        $offset = $args[0];
        $size = $args[1];
        if(gettype($offset)!=='integer'||gettype($size)!=='integer')
            throw new Exception("Parameter invalid: \"memtxt\" called with OFFSET and SIZE they must be integer");

        $langItem = shmop_read($shmop, $offset, $size);
        $lang = getlang();
        $matches = array();
        if(preg_match("/(?<=<".$lang.">)(.|\r|\n)*(?=<\/".$lang.">)/m",$langItem,$matches)!==1 || !isset($matches[0]))
            throw new Exception("No definition for language named \"".$lang."\".");
        return $matches[0];
    }
    else throw new Exception("Parameter invalid: \"memtxt\" not accepting three or more args");
}
