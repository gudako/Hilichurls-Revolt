<?php
require_once 'include/initialize.php';
require_once 'config.php';
require_once '_langtab.php';

function get_lang(){
    if(!isset($_COOKIE['lang'])) return 'en';
    $lang_cookie = $_COOKIE['lang'];
    $lang_set = ['en', 'zh'];
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

function text(string $textcode){
    $lang = get_lang();
    if(isDebug())
        return json_decode(file_get_contents('lang.json', true), true)[$textcode][$lang];

    if(!isset($GLOBALS['langtab'])) eval(substr(file_get_contents('lang/_langtab.php'),5));

    $langtab = $GLOBALS['langtab'];
    $textcode_hash = substr(sha1($textcode), 0, 10);

    $lang_tuple = $langtab[$textcode_hash];
    $currIndex = $lang_tuple[0];
    $size = $lang_tuple[1];

    if(!isset($GLOBALS['shmop_lang']))
        $GLOBALS['shmop_lang'] = shmop_open(1, 'a', 0600, 100000);
    $langItem = shmop_read($GLOBALS['shmop_lang'], $currIndex, $size);
    if($langItem === false)
        throw new Exception("Failed to open SHMOP object...");
    $matches = array();
    if(preg_match("/(?<=<".$lang.">).*(?=<\/".$lang.">)/",$langItem,$matches)!==1 || !isset($matches[0]))
        throw new Exception("No definition for language named \"".$lang."\".");
    return $matches[0];
}
