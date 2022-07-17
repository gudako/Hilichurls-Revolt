<?php
namespace local;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

/**
 * A class that manages the database connection and its corresponding actions of the server.
 */
final class DatabaseSystem
{
    private static \mysqli $_database;
    private static ConfigSystem $config;

    function __construct(){
        if(!isset(self::$config)) self::$config = new ConfigSystem();
    }

    /**
     * Get the connection to the MySQL database.
     * @param bool $withHostname Whether the connection is based on the schema of the config hostname.
     * @return \mysqli The MySQL connection object.
     */
    function GetConnection(bool $withHostname=true): \mysqli
    {
        if(isset($this::$_database)) return $this::$_database;
        $this::$_database = new \mysqli($withHostname?self::$config->GetDBHostname():null,
            self::$config->GetDBUsername(), self::$config->GetDBPassword());
        return $this::$_database;
    }

    /**
     * Gets whether the database schema of the config hostname exists.
     * @return bool Returns true if the schema exists.
     */
    function IsInitialized():bool{
        return (new self())->GetConnection(false)
            ->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" .
                self::$config->GetDBHostname()."'")->fetch_row()!==null;
    }

    /**
     * @param string $detail The full readable detail of the error&exception to be logged.
     * @param string|null $errnoStr The error&exception type for indexing.
     * @param int $importance The importance of the error&exception for indexing.
     * @return int The ID of the log in the database.
     */
    function MakeLog(string $detail, string $errnoStr = null, int $importance=0):int{
        throw new \Exception();
        //todo implementation
    }
}