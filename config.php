<?php

class Config
{
    private static $isDebug;
    private static $dbHostname;
    private static $dbUsername;
    private static $dbPassword;

    function __construct()
    {
        $config = json_decode(file_get_contents(__DIR__.'/config.json'), true);
        if (!isset(self::$isDebug)) self::$isDebug = $config['mode_debug'] == 'true';
        if (!isset(self::$dbHostname)) self::$dbHostname = $config['db_hostname'];
        if (!isset(self::$dbUsername)) self::$dbUsername = $config['db_username'];
        if (!isset(self::$dbPassword)) self::$dbPassword = $config['db_password'];
    }

    function IsDebug(): bool
    {
        return self::$isDebug;
    }

    function GetDBHostname(): string
    {
        return self::$dbHostname;
    }

    function GetDBUsername(): string
    {
        return self::$dbUsername;
    }

    function GetDBPassword(): string
    {
        return self::$dbPassword;
    }
}