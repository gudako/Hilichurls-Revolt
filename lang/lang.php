<?php
require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";
use Game\Config;

function getlang(){
    if(!isset($_COOKIE['lang'])) return 'en';
    $lang_cookie = $_COOKIE['lang'];
    $config=new Config();
    $lang_set = $config->GetAllLanguages();
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

function memtxt(int $offset, int $size){
    $config = new Config();
    $shmop = shmop_open($config->GetShmopIdLang(), 'a', 0600, 1);
    $langItem = shmop_read($shmop, $offset, $size);
    $lang = getlang();
    $matches = array();
    if(preg_match("/(?<=<".$lang.">)(.|\r|\n)*(?=<\/".$lang.">)/m",$langItem,$matches)!==1 || !isset($matches[0]))
        throw new Exception("No definition for language named \"".$lang."\".");
    return $matches[0];
}
