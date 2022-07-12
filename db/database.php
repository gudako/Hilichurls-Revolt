<?php
namespace Game;
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
use mysqli;

class Database
{
    private static mysqli $_database;
    private static Config $config;

    function __construct(){
        if(!isset(self::$config)) self::$config = new Config();
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
}