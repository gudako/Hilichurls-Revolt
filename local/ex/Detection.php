<?php
namespace local\ex;
use Exception;

const POSTEX_CONTINUABLE = 4, POSTEX_HANGINPLACE = 2, POSTEX_REDIRECT = 1;
const SEVERITY_UNDETECTED = 0, SEVERITY_CLIENTSIDE_REFLECT = 1, SEVERITY_PARTIAL = 2, SEVERITY_BLOCKING = 4, SEVERITY_DESTRUCTIVE = 8;

abstract class Detection extends Exception
{
    abstract function GetSeverity():int;
    abstract function GetPostExAction():int;
}
