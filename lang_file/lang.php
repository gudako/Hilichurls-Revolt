<?php require 'include/initialize.php';

function get_lang(){
    if(!isset($_COOKIE['lang']))return 'en';
    $lang_cookie=$_COOKIE['lang'];
    $lang_set = ['en', 'zh'];
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

function text(string $textcode){
    $lang = get_lang();
    if(isset($GLOBALS["DEBUG"]) || !isset($_SESSION['lang_text']))
        $_SESSION['lang_text'] = json_decode(file_get_contents('lang.json', true), true);
    return $_SESSION['lang_text'][$textcode][$lang];
}
