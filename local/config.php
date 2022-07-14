<?php
namespace local;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
use DateInterval;
use DateTime;

class config
{
    private static bool $isDebug;
    private static string $dbHostname, $dbUsername, $dbPassword;
    private static int $shmopLangMaxsz, $shmopAchvMaxsz, $shmopIdMaintenance, $shmopIdLang, $shmopIdAchv;
    private static array $allLanguages;
    private static int $shmopHashtableMulti;

    function __construct(){
        if(!isset(self::$isDebug, self::$dbHostname, self::$dbUsername, self::$dbPassword, self::$shmopLangMaxsz,
            self::$shmopLangMaxsz, self::$shmopIdMaintenance, self::$shmopIdLang, self::$allLanguages)){
            $config = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/config.json'), true);
            self::$isDebug = $config['mode_debug'] == 'true';
            self::$dbHostname = $config['db_hostname'];
            self::$dbUsername = $config['db_username'];
            self::$dbPassword = $config['db_password'];
            self::$shmopLangMaxsz = $config['shmop_lang_max_size'];
            self::$shmopAchvMaxsz = $config['shmop_achv_max_size'];
            self::$shmopIdMaintenance = $config['shmop_id_maintenance'];
            self::$shmopIdLang = $config['shmop_id_lang'];
            self::$shmopIdAchv = $config['shmop_id_achv'];
            self::$allLanguages = $config['all_languages'];
            self::$shmopHashtableMulti = $config['shmop_hashtable_multi'];
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

    function GetShmopLangMaxsz(): int{
        return self::$shmopLangMaxsz;
    }

    function GetShmopAchvMaxsz(): int{
        return self::$shmopAchvMaxsz;
    }

    function GetShmopIdMaintenance(): int{
        return self::$shmopIdMaintenance;
    }

    function GetShmopIdLang(): int{
        return self::$shmopIdLang;
    }

    function GetShmopIdAchv(): int{
        return self::$shmopIdAchv;
    }

    function GetAllLanguages(): array{
        return self::$allLanguages;
    }

    function IsServerDown(): bool{
        if(shmop_open(self::$shmopIdMaintenance, 'a', 0777, 1)===false)return true;
        if(shmop_open(self::$shmopIdLang, 'a', 0777, 1)===false)return true;
        if(!(new database())->IsInitialized())return true;
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

    function GetShmopHashtableMulti():int{
        return self::$shmopHashtableMulti;
    }
}