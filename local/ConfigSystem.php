<?php
namespace local;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

/**
 * A class that represents the config settings as in /local/config.json
 */
class ConfigSystem
{
    private static bool $isDebug;
    private static string $dbHostname, $dbUsername, $dbPassword;
    private static int $shmopSizeLang, $shmopSizeAchv, $shmopIdMaintenance, $shmopIdLang, $shmopIdAchv;
    private static array $allLanguages;
    private static int $shmopHashtableMulti;
    private static string $logEncryptKey;

    function __construct(){
        if(!isset(self::$isDebug, self::$dbHostname, self::$dbUsername, self::$dbPassword, self::$shmopSizeLang,
            self::$shmopSizeLang, self::$shmopIdMaintenance, self::$shmopIdLang, self::$allLanguages,
            self::$shmopHashtableMulti, self::$logEncryptKey)){
            $config = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/config.json'), true);
            self::$isDebug = $config['mode_debug'] == 'true';
            self::$dbHostname = $config['db_hostname'];
            self::$dbUsername = $config['db_username'];
            self::$dbPassword = $config['db_password'];
            self::$shmopSizeLang = $config['shmop_size_lang'];
            self::$shmopSizeAchv = $config['shmop_size_achv'];
            self::$shmopIdMaintenance = $config['shmop_id_maintenance'];
            self::$shmopIdLang = $config['shmop_id_lang'];
            self::$shmopIdAchv = $config['shmop_id_achv'];
            self::$allLanguages = $config['all_languages'];
            self::$shmopHashtableMulti = $config['shmop_hashtable_multi'];
            self::$logEncryptKey = $config['logging_encrypt_key'];
        }
    }

    /**
     * Gets whether the website is in debug mode.
     * @return bool Returns true while in debug mode.
     */
    function IsDebug(): bool{
        return self::$isDebug;
    }

    /**
     * Gets the database hostname.
     * @return string Returns the database hostname as string.
     */
    function GetDBHostname(): string{
        return self::$dbHostname;
    }

    /**
     * Gets the database username.
     * @return string Returns the database username as string.
     */
    function GetDBUsername(): string{
        return self::$dbUsername;
    }

    /**
     * Gets the database password.
     * @return string Returns the database password as string.
     */
    function GetDBPassword(): string{
        return self::$dbPassword;
    }

    /**
     * Gets the size of the Shmop memory object for storing the language-text data.
     * @return int Returns the Shmop object's size in byte.
     */
    function GetShmopSizeLang(): int{
        return self::$shmopSizeLang;
    }

    /**
     * Gets the size of the Shmop memory object for storing the achievements data.
     * @return int Returns the Shmop object's size in byte.
     */
    function GetShmopSizeAchv(): int{
        return self::$shmopSizeAchv;
    }

    /**
     * Gets the ID of the Shmop memory object for storing the maintenance data.
     * @return int Returns the Shmop object's ID.
     */
    function GetShmopIdMaintenance(): int{
        return self::$shmopIdMaintenance;
    }

    /**
     * Gets the ID of the Shmop memory object for storing the language-text data.
     * @return int Returns the Shmop object's ID.
     */
    function GetShmopIdLang(): int{
        return self::$shmopIdLang;
    }

    /**
     * Gets the ID of the Shmop memory object for storing the achievements data.
     * @return int Returns the Shmop object's ID.
     */
    function GetShmopIdAchv(): int{
        return self::$shmopIdAchv;
    }

    /**
     * Gets an array that has all available languages currently in the webpages.
     * @return array Returns the array containing all language codes as strings.
     */
    function GetAllLanguages(): array{
        return self::$allLanguages;
    }

    /**
     * @return int
     */
    function GetShmopHashtableMulti():int{
        return self::$shmopHashtableMulti;
    }

    /**
     * @return string
     */
    function GetLogEncryptKey():string{
        return self::$logEncryptKey;
    }

    /**
     * Gets whether the server is currently not up for use,
     * by checking the initialization state of Shmop memory objects and database.
     * @return bool Returns true if the server is down. The server is down as long as one initialization is not done.
     */
    function IsServerDown(): bool{
        if(shmop_open(self::$shmopIdMaintenance, 'a', 0600, 1)===false)return true;
        if(shmop_open(self::$shmopIdLang, 'a', 0600, 1)===false)return true;
        if(shmop_open(self::$shmopIdAchv, 'a', 0600, 1)===false)return true;
        if(!(new DatabaseSystem())->IsInitialized())return true;
        return false;
    }

    /**
     * Get whether the server is in maintenance, and, if true, the time before&after the maintenance.
     * @return false|\DateInterval Returns false if no maintenance is at the place or scheduled;
     * Returns a negative {@link \DateInterval} for scheduled maintenance, or a positive {@link \DateInterval} for on-place maintenance.
     * @throws \Exception When the string stored in maintenance Shmop object is neither zeros nor valid {@link \DateTime} string.
     * @throws \Exception When the method is called while the Shmop is uninitialized.
     */
    function GetMaintenanceTime(): false|\DateInterval{
        $shmopMaint = shmop_open(self::$shmopIdMaintenance, 'a', 0600, 26);
        if($shmopMaint===false)
            throw new \Exception("Trying to get the maintenance state while the server is down.",EX_CODE_IMPORTANT);

        $maintStr = shmop_read($shmopMaint, 0, 26);
        if($maintStr === str_repeat("0", 26)) return false;

        $maintTime = new \DateTimeImmutable($maintStr);
        return $maintTime->diff(new \DateTimeImmutable());
    }

    /**
     * Get whether the server is in maintenance. Does not count scheduled maintenance.
     * @return bool Returns true when the server is in maintenance.
     * @throws \Exception Exceptions from {@link GetMaintenanceTime}.
     */
    function InMaintenance():bool{
        $maintTime = $this->GetMaintenanceTime();
        return $maintTime !==false && $maintTime->format('%R')=='+';
    }

    /**
     * Get whether the server is not in maintenance or has been scheduled a maintenance.
     * @return bool Returns true when the server is neither in on-place nor scheduled maintenance.
     * @throws \Exception Exceptions from {@link GetMaintenanceTime}.
     */
    function NoMaintenance():bool{
        return $this->GetMaintenanceTime()===false;
    }
}