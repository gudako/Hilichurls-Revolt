<?php
namespace Game;
require_once $_SERVER['DOCUMENT_ROOT'].'/script/database.php';
use DateInterval, DateTime;

class Config
{
    private static bool $isDebug;
    private static string $dbHostname, $dbUsername, $dbPassword;
    private static int $langMaxSize, $shmopIdMaintenance, $shmopIdLang;
    private static array $allLanguages;

    function __construct(){
        if(!isset(self::$isDebug, self::$dbHostname, self::$dbUsername, self::$dbPassword, self::$langMaxSize,
            self::$langMaxSize, self::$shmopIdMaintenance, self::$shmopIdLang, self::$allLanguages)){
            $config = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/config.json'), true);
            self::$isDebug = $config['mode_debug'] == 'true';
            self::$dbHostname = $config['db_hostname'];
            self::$dbUsername = $config['db_username'];
            self::$dbPassword = $config['db_password'];
            self::$langMaxSize = $config['lang_max_size'];
            self::$shmopIdMaintenance = $config['shmop_id_maintenance'];
            self::$shmopIdLang = $config['shmop_id_lang'];
            self::$allLanguages = $config['all_languages'];
        }
    }

    function IsDebug(): bool{
        return self::$isDebug;
    }

    function GetDBHostname(): string{
        return self::$dbHostname;
    }

    function GetDBUsername(): string{
        return self::$dbUsername;
    }

    function GetDBPassword(): string{
        return self::$dbPassword;
    }

    function GetLangMaxSize(): int{
        return self::$langMaxSize;
    }

    function GetShmopIdMaintenance(): int{
        return self::$shmopIdMaintenance;
    }

    function GetShmopIdLang(): int{
        return self::$shmopIdLang;
    }

    function GetAllLanguages(): array{
        return self::$allLanguages;
    }

    function IsServerDown(): bool{
        if(shmop_open(self::$shmopIdMaintenance, 'a', 0777, 1)===false)return true;
        if(shmop_open(self::$shmopIdLang, 'a', 0777, 1)===false)return true;
        if(!(new Database())->IsInitialized())return true;
        return false;
    }

    function GetMaintenanceTime(): false|DateInterval{
        $shmopMaintenance = shmop_open(self::$shmopIdMaintenance, 'a', 0600, 26);
        $maintenanceDateStr = shmop_read($shmopMaintenance, 0, 26);
        if($maintenanceDateStr == '00000000000000000000000000') return false;
        $maintenanceTime = new DateTime($maintenanceDateStr);
        $now = new DateTime();
        return $maintenanceTime->diff($now);
    }

    function InMaintenance():bool{
        $maintainTime = $this->GetMaintenanceTime();
        return $maintainTime !==false && $maintainTime->format('%R')=='+';
    }

    function PreMaintenance():bool{
        $maintainTime = $this->GetMaintenanceTime();
        return $maintainTime !==false && $maintainTime->format('%R')=='-';
    }

    function NoMaintenance():bool{
        return $this->GetMaintenanceTime()===false;
    }
}