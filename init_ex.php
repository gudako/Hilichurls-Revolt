<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/utils/AES-256-CBC.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/utils/getThrowableTraceAsString.php";
use local\DatabaseSystem;

const errnoRef = [
    E_ERROR => "E_ERROR",
    E_WARNING => "E_WARNING",
    E_PARSE => "E_PARSE",
    E_NOTICE => "E_NOTICE",
    E_CORE_ERROR => "E_CORE_ERROR",
    E_CORE_WARNING => "E_CORE_WARNING",
    E_COMPILE_ERROR => "E_COMPILE_ERROR",
    E_COMPILE_WARNING => "E_COMPILE_WARNING",
    E_USER_ERROR => "E_USER_ERROR",
    E_USER_WARNING => "E_USER_WARNING",
    E_USER_NOTICE => "E_USER_NOTICE",
    E_STRICT => "E_STRICT",
    E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
    E_DEPRECATED => "E_DEPRECATED",
    E_USER_DEPRECATED => "E_USER_DEPRECATED",
    E_ALL => "E_ALL"
];

$handler=function (string $trace, int $errno= null){
    $dbLogId = null;
    try {
        $dbLogId = (new DatabaseSystem())->MakeLog($trace,$errno===null?null:errnoRef[$errno]);
    }
    catch (Throwable){}
    $httpPrefix = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $lang = 'en';
    try{
        $lang = getlang();
    }catch (Throwable){}

    if($dbLogId!==null)$query="logcode=".str_pad(strval($dbLogId),10,'0',STR_PAD_LEFT);
    else $query= "msg=".bin2hex(encrypt($trace, (new \local\ConfigSystem())->GetLogEncryptKey()));
    $timeSeed = (new DateTimeImmutable())->format('Y-m-d');
    $hash = sha1($query.$timeSeed."You got that?");

    header("Location: $httpPrefix://{$_SERVER['HTTP_HOST']}/error.php?$query&auth=$hash&lang=$lang");
};

$exHandler = function (Throwable $exception)use($handler){
    $trace = "";
    $writeTrace = function (Throwable $exception)use(&$writeTrace, &$trace){
        if($exception->getPrevious()!==null) {
            $writeTrace($exception->getPrevious());
        }
        $trace = "{$exception->getMessage()}\r\n".get_class($exception)." (code {$exception->getCode()}) ".
            "thrown in file \"{$exception->getFile()}\" on line {$exception->getLine()}.\r\n".
            (count($exception->getTrace()) == 0?"":"Stack trace:\r\n".getThrowableTraceAsString($exception)).
            ($exception->getPrevious()===null?"":"↓ PREVIOUS EXCEPTION ↓\r\n").$trace;
    };
    $writeTrace($exception);
    $handler($trace);
};

$errHandler=function(int$errno,string$errstr,string$errfile=null,int$errline=null,array$errcontext=null)use($handler):bool{
    $trace = "$errstr\r\n".errnoRef[$errno]." thrown".($errfile===null?"":(" in file \"$errfile\"".
            ($errline===null?"":" on line $errline"))).".";
    $handler($trace, $errno);
    return false;
};

set_exception_handler($exHandler);
set_error_handler($errHandler);

if((new \local\ConfigSystem())->IsDebug()){
    error_reporting(E_ALL);
}
else{
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}
