<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/utils/AES-256-CBC.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/utils/getThrowableTraceAsString.php";
use local\DatabaseSystem;

$handler = function (Throwable $throwable){
    $trace = getThrowableTraceAsString($throwable);
    $db_logid = null;
    try {
        $db_logid = (new DatabaseSystem())->LogThrowable($throwable, $trace);
    }
    catch (Throwable){}
    $httpPrefix = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $lang = 'en';
    try{
        $lang = getlang();
    }catch (Throwable){}

    if($db_logid!==null)$query="logcode=".str_pad(strval($db_logid),10,'0',STR_PAD_LEFT);
    else $query= "msg=".encrypt($trace, (new \local\ConfigSystem())->GetLogEncryptKey());
    header("Location: $httpPrefix://{$_SERVER['HTTP_HOST']}/error.php?$query&lang=$lang");
};

set_exception_handler($handler);
set_error_handler($handler);

