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
    E_ALL => "E_ALL",
    E_ALL + 1 => "E_EXCEPTION"
];

$handler=function (string $trace, int $errno, bool $showDetails = false, int $importance =0){
    $lowLevel = $errno&(E_USER_NOTICE|E_NOTICE|E_USER_WARNING|E_WARNING|E_USER_DEPRECATED|E_DEPRECATED)!==0;
    if($showDetails||$lowLevel){
        echo "<div style='color: ".($lowLevel?"darkred":"darkorange").";'>".($lowLevel?"Notice":"Error").
            " <b>".errnoRef[$errno]."</b>: ".str_replace(["\r\n","\n"],"<br>",$trace)."</div>";
        return;
    }

    $dbLogId = null;
    try {
        $dbLogId = (new DatabaseSystem())->MakeLog($trace,errnoRef[$errno],$importance);
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

    header("Location: $httpPrefix://{$_SERVER['HTTP_HOST']}/error?$query&auth=$hash&lang=$lang");
};

$exHandler = function (Throwable $exception)use($handler){
    $trace = "";
    $writeTrace = function (Throwable $exception)use(&$writeTrace, &$trace){
        if($exception->getPrevious()!==null) {
            $writeTrace($exception->getPrevious());
        }
        $trace = "{$exception->getMessage()}\r\n".get_class($exception)." (code {$exception->getCode()}) ".
            "thrown in file \"{$exception->getFile()}\" on line {$exception->getLine()}\r\n".
            (count($exception->getTrace()) == 0?"":"Stack trace:\r\n".getThrowableTraceAsString($exception)).
            ($exception->getPrevious()===null?"":"↓ PREVIOUS EXCEPTION ↓\r\n").$trace;
    };
    $writeTrace($exception);
    $handler($trace,E_ALL+1, ($exception->getCode()&ERROR_DEV)!==0,
        ($exception->getCode()&ERROR_HIGHLIGHTED)!==0?1:0);
};

$errHandler=function(int$errno,string$errstr,string$errfile=null,int$errline=null,array$errcontext=null)use($handler):bool{
    $trace = "$errstr\r\n".errnoRef[$errno]." thrown".($errfile===null?"":(" in file \"$errfile\"".
            ($errline===null?"":" on line $errline")));
    $handler($trace, $errno, (new \local\ConfigSystem())->IsDebug(), 2);
    return false;
};

if((new \local\ConfigSystem())->IsDebug()){
    error_reporting(E_ALL);
}
else{
    set_exception_handler($exHandler);
    set_error_handler($errHandler);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}
