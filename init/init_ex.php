<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

const errnoRef = [
    -1 => "E_EXCEPTION",
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

/**
 * @param string $detail The full detail of the error or exception.
 * @param int $errno For errors, this is the corresponding "$errno"; For exceptions, this is always -1.
 * @param int $importance The importance value to be logged into the database for indexing.
 * If this is -1, the error or exception details will be directly shown in the page rather than being logged.
 * SECURITY NOTE: Only show the details in debug mode or to a trusted administrator!
 * @return void Returning nothing.
 */
$handler=function (string $detail, int $errno = -1, int $importance =0)
{
    $config =new \local\ConfigSystem();

    // Check whether the message is to be directly displayed.
    if($importance===-1){
        // Directly shows the message to client without logging.
        $lowLevel = $errno&(E_USER_NOTICE|E_NOTICE|E_USER_WARNING|E_WARNING|E_USER_DEPRECATED|E_DEPRECATED)!==0;
        echo "<div style='color: ".($lowLevel?"darkred":"darkorange").";'>".($lowLevel?"Notice":"Error").
            " <b>".errnoRef[$errno]."</b>: ".str_replace(["\r\n","\n"],"<br>",$detail)."</div>";
        return;
    }

    // Try to log the error or exception into database.
    $dbLogId = null;
    try {
        $dbLogId = (new \local\DatabaseSystem())->MakeLog($detail,errnoRef[$errno],$importance);
    } catch (Throwable){}

    // Try getting the client language.
    $lang = "en";
    try{ $lang = getlang(); } catch (Throwable){}

    // Generate the query string for the error page.
    if($dbLogId!==null)$query="logcode=".str_pad(strval($dbLogId),10,'0',STR_PAD_LEFT);
    else $query= "msg=".bin2hex(\utils\AES_256_CBC::Encrypt($detail, $config->GetLogEncryptKey()));
    $timeSeed = (new DateTimeImmutable())->format('Y-m-d');
    $hash = sha1($query.$timeSeed."You got that?");

    // Redirect to the error page.
    $httpPrefix = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    header("Location: $httpPrefix://{$_SERVER['HTTP_HOST']}/error?$query&auth=$hash&lang=$lang");
};

/**
 * The handler for exceptions. It's a callback for predefined call.
 * @param Throwable $exception The exception to be handled.
 * @return void Returning nothing.
 */
$exHandler = function (Throwable $exception)use($handler){
    $trace = "";
    $writeTrace = function (Throwable $exception)use(&$writeTrace, &$trace){
        if($exception->getPrevious()!==null) {
            $writeTrace($exception->getPrevious());
        }
        $trace = "{$exception->getMessage()}\r\n".get_class($exception)." (code {$exception->getCode()}) ".
            "thrown in file \"{$exception->getFile()}\" on line {$exception->getLine()}\r\n".
            (count($exception->getTrace()) == 0?"":"Stack trace:\r\n".
                utils\StackTraceDecoder::GetThrowableTraceAsString($exception)).
            ($exception->getPrevious()===null?"":"↓ PREVIOUS EXCEPTION ↓\r\n").$trace;
    };
    $writeTrace($exception);
    $importance = match ($exception->getCode()) {
        EX_CODE_EXPECTED => 0,
        EX_CODE_IMPORTANT => 1,
        EX_CODE_SHOWDETAILS => -1,
        default => 0,
    };
    $handler($trace,-1, $importance);
};


/**
 * The handler for errors. It's a callback for predefined call.
 * @param int $errno A code representing the error's type as found in {@link errnoRef}.
 * @param string $errstr The message of the error.
 * @param string|null $errfile The file that triggered the error.
 * @param int|null $errline The line of the code that triggered the error.
 * @param array|null $errcontext Deprecated.
 * @return false Returns false, indicating the default handler will not be called after then.
 */
$errHandler=function(int $errno, string $errstr, string $errfile = null, int $errline = null,
                     array $errcontext = null) use ($handler):bool {
    $trace = "$errstr\r\n".errnoRef[$errno]." thrown".($errfile===null?"":(" in file \"$errfile\"".
            ($errline===null?"":" on line $errline")));
    $handler($trace, $errno, (new \local\ConfigSystem())->IsDebug(), 2);
    return false;
};

// Not setting any new handler for debug mode.
if((new \local\ConfigSystem())->IsDebug()){
    error_reporting(E_ALL);
}
else{
    set_exception_handler($exHandler);
    set_error_handler($errHandler);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}
