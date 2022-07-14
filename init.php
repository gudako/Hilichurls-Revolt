<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
use local\DatabaseSystem, local\ex\Detection;

set_exception_handler(function(Exception $exception) {
    try {
        (new DatabaseSystem())->LogThrowable($exception);
    }
    catch (Exception){}
    $detected = $exception instanceof Detection;
    $postEx = !$detected? \local\ex\POSTEX_REDIRECT:$exception->GetPostExAction();
    $severity = !$detected?\local\ex\SEVERITY_UNDETECTED:$exception->GetSeverity();

});

set_error_handler(function (Error $error){
    try {
        (new DatabaseSystem())->LogThrowable($error);
    }
    catch (Exception){}
});
