<?php
namespace local;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
use mysqli;

class DatabaseSystem
{
    private static mysqli $_database;
    private static ConfigSystem $config;

    function __construct(){
        if(!isset(self::$config)) self::$config = new ConfigSystem();
    }

    function GetConnection(bool $withHostname=true): mysqli
    {
        if(isset($this::$_database)) return $this::$_database;
        $this::$_database = new mysqli($withHostname?self::$config->GetDBHostname():null,
            self::$config->GetDBUsername(), self::$config->GetDBPassword());
        return $this::$_database;
    }

    function IsInitialized():bool{
        return (new self())->GetConnection(false)
            ->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" .
                self::$config->GetDBHostname()."'")->fetch_row()!==null;
    }

    function LogThrowable(\Throwable $throwable, string $data):int{

    }
}