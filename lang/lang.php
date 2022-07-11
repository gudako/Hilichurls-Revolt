<?php
require_once 'config.php';
require_once '_langtab.php';
use Game\Config;

function get_lang(){
    if(!isset($_COOKIE['lang'])) return 'en';
    $lang_cookie = $_COOKIE['lang'];
    $config=new Config();
    $lang_set = $config->GetAllLanguages();
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

function text(string $textcode){
    $lang = get_lang();
    $config=new Config();
    if($config->IsDebug())
        return json_decode(file_get_contents('lang.json', true), true)[$textcode][$lang];

    if(!isset($GLOBALS['langtab'])) eval(substr(file_get_contents('lang/_langtab.php'),5));

    $langtab = $GLOBALS['langtab'];
    $textcode_hash = substr(sha1($textcode), 0, 10);

    $lang_tuple = $langtab[$textcode_hash];
    $currIndex = $lang_tuple[0];
    $size = $lang_tuple[1];

    $shmopHere = shmop_open($config->GetShmopIdLang(), 'a', 0600, 1);
    $langItem = shmop_read($shmopHere, $currIndex, $size);

    $matches = array();
    if(preg_match("/(?<=<".$lang.">)(.|\r|\n)*(?=<\/".$lang.">)/m",$langItem,$matches)!==1 || !isset($matches[0]))
        throw new Exception("No definition for language named \"".$lang."\".");
    return $matches[0];
}
