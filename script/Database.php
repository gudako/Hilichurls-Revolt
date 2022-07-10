<?php

namespace Game;

class Database
{
    private static $_database;

    function GetConnection(): \mysqli
    {
        if(isset($this::$_database)) return $this::$_database;
        $config = json_decode(file_get_contents("config.json"), true);
        $this::$_database = new \mysqli($config['hostname'], $config['username'], $config['password']);
        return $this::$_database;
    }


}