<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
use local\ConfigSystem;

abstract class AchievementSystem
{
    protected static \Shmop $shmop;
    protected static ConfigSystem $config;

    protected static int $prefixHashSize;
    protected static int $prefixOffsetSize;
    protected static int $dataOffset;

    private string $data;
    protected int $memloc, $memsize;
    protected bool $isCategory;

    function __construct()
    {
        if(!isset(self::$config)) self::$config = new ConfigSystem();
        if(!isset(self::$shmop, self::$prefixHashSize, self::$prefixOffsetSize, self::$dataOffset)){
            self::$shmop = shmop_open(self::$config->GetShmopIdAchv(), 'a', 0600, self::$config->GetShmopAchvMaxsz());
            if(self::$shmop===false)throw new \Error("Failed to open the ACHV shmop object.",SEVERITY_SERVER_CORRUPT);
            self::$dataOffset = shmop_read(self::$shmop, 0, 2);
            self::$prefixHashSize = shmop_read(self::$shmop, 2, 1);
            self::$prefixOffsetSize = shmop_read(self::$shmop, 3, 1);}

        $mem = self::memachv(...func_get_args());
        $this->data = $mem['data'];
        $this->memloc = $mem['offset'];
        $this->memsize = $mem['size'];
        $this->isCategory = $mem['is_category'];
    }

    private static function memachv():array{
        $config = new ConfigSystem();
        $shmop = shmop_open($config->GetShmopIdAchv(),"a",0600,$config->GetShmopAchvMaxsz());
        $args = func_get_args();
        if(count($args)===1){
            if(gettype($args[0])==='array'){
                if(count($args[0])!==2||gettype($args[0][0])!=='integer'||gettype($args[0][1])!=='integer')
                    throw new \TypeError("Function called with invalid array, must be [int \$offset, int \$size]");
                return self::memachv($args[0][0], $args[0][1]);
            }
            if(gettype($args[0])!=='string')
                throw new \TypeError("Function called with unrecognized type");
            return self::memachv(loadMemByTextcode($shmop, $args[0]));
        }
        elseif(count($args)===2){
            $offset = $args[0];
            $size = $args[1];
            if(gettype($offset)!=='integer'||gettype($size)!=='integer')
                throw new \TypeError("\$offset and \$size they must be integers");

            $offsetSz = shmop_read($shmop,3, 1);
            $achvData = shmop_read($shmop, $offset, $size);
            $isCategory = bin2hex(substr($achvData,0,$offsetSz))==str_repeat("00",$offsetSz);
            $dataOffset = $offset + ($isCategory?3:1)*$offsetSz;
            $dataSize = $size - ($isCategory?3:1)*$offsetSz;
            $data = shmop_read($shmop, $dataOffset, $dataSize);

            return array(
                "is_category" =>$isCategory,
                "offset"=>$offset,
                "size"=>$size,
                "data"=>$data
            );
        }
        else throw new \ArgumentCountError("Argument count should be 1 or 2");
    }

    public function GetJson(){return $this->data;}

    abstract function GetNext():AchievementSystem|false;
}