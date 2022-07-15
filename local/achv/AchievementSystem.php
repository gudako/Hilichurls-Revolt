<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

use mysql_xdevapi\Exception;
use Shmop;
use local\ConfigSystem;

abstract class AchievementSystem
{
    private static Shmop $shmop;
    private static ConfigSystem $config;

    protected static int $prefixHashSize;
    protected static int $prefixOffsetSize;
    protected static int $dataOffset;

    private int $memloc;
    private int $memsize;

    function __construct()
    {
        if(!isset(self::$config)) self::$config = new ConfigSystem();
        if(!isset(self::$shmop, self::$prefixHashSize, self::$prefixOffsetSize, self::$dataOffset)){
            self::$shmop = shmop_open(self::$config->GetShmopIdAchv(), 'a', 0600, self::$config->GetShmopAchvMaxsz());
            if(self::$shmop===false)throw new Exception("Failed to open the ACHV shmop object.");
            self::$dataOffset = shmop_read(self::$shmop, 0, 2);
            self::$prefixHashSize = shmop_read(self::$shmop, 2, 1);
            self::$prefixOffsetSize = shmop_read(self::$shmop, 3, 1);
        }
    }

    abstract function getString();
}