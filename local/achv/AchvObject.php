<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

/**
 * An abstract class that represents an object of an achievement, whether a category or item.
 */
 abstract class AchvObject
{
    protected static \Shmop $shmop;
    protected static \local\ConfigSystem $config;

    protected static int $prefixHashSize;
    protected static int $prefixOffsetSize;
    protected static int $dataOffset;

    protected array $data;
    protected int $memloc, $memsize;
    protected bool $isCategory;

    /**
     * Construct the object, by passing
     * (1). A string representing the name of an achievement;
     * (2). A int with the memory offset, another with the size;
     * (3). The two ints in (2) packed into an array.
     */
    function __construct()
    {
        if(!isset(self::$config)) self::$config = new \local\ConfigSystem();
        if(!isset(self::$shmop, self::$prefixHashSize, self::$prefixOffsetSize, self::$dataOffset)){
            self::$shmop = shmop_open(self::$config->GetShmopIdAchv(), 'a', 0600, self::$config->GetShmopSizeAchv());
            if(self::$shmop===false)throw new \Error("Failed to open the ACHV shmop object.",EX_CODE_IMPORTANT);
            self::$dataOffset =hexdec(bin2hex(shmop_read(self::$shmop, 0, 2)));
            self::$prefixHashSize = hexdec(bin2hex(shmop_read(self::$shmop, 2, 1)));
            self::$prefixOffsetSize = hexdec(bin2hex(shmop_read(self::$shmop, 3, 1)));}

        $mem = self::memachv(...func_get_args());
        $this->data = $mem["data"];
        $this->memloc = $mem["offset"];
        $this->memsize = $mem["size"];
        $this->isCategory = $mem["is_category"];
    }

    private static function memachv():array{
        $config = new \local\ConfigSystem();
        $shmop = shmop_open($config->GetShmopIdAchv(),'a',0600,$config->GetShmopSizeAchv());
        $args = func_get_args();
        if(count($args)===1){
            if(gettype($args[0])==="array"){
                if(count($args[0])!==2||gettype($args[0][0])!=="integer"||gettype($args[0][1])!=="integer")
                    throw new \TypeError("Function called with invalid array, must be [int \$offset, int \$size]");
                return self::memachv($args[0][0], $args[0][1]);
            }
            if(gettype($args[0])!=="string")
                throw new \TypeError("Function called with unrecognized type");
            return self::memachv(\utils\TextcodeParser::ParseByTextcode($shmop, $args[0]));
        }
        elseif(count($args)===2){
            $offset = $args[0];
            $size = $args[1];
            if(gettype($offset)!=="integer"||gettype($size)!=="integer")
                throw new \TypeError("\$offset and \$size they must be integers");

            $offsetSz = hexdec(bin2hex(shmop_read($shmop,3, 1)));
            $achvData = shmop_read($shmop, $offset, $size);
            $isCategory = bin2hex(substr($achvData,0,$offsetSz))==str_repeat("00",$offsetSz);
            $dataOffset = $offset + ($isCategory?3:1)*$offsetSz;
            $dataSize = $size - ($isCategory?3:1)*$offsetSz;
            $data = json_decode(shmop_read($shmop, $dataOffset, $dataSize),true);

            return array(
                "is_category" =>$isCategory,
                "offset"=>$offset,
                "size"=>$size,
                "data"=>$data
            );
        }
        else throw new \ArgumentCountError("Argument count should be 1 or 2");
    }

    /**
     * Get the next ACHV object in memory of the corresponding type.
     * @return AchvObject|false Returns false if there's no adjacent next object of the same type.
     * Otherwise, return the next object.
     */
    abstract function Next():AchvObject|false;

    /**
     * Gets the readable name of the ACHV object in your current language.
     * @return string The name of the ACHV object.
     */
    function GetName(): string{
        if(!isset($this->data["name"]))throw new \Error("The target data has no key named \"name\".",EX_CODE_IMPORTANT);
        return memtxt("achv_".$this->data["name"]);
    }

    /**
     * Gets the array data of this ACHV object.
     * @return array The result array.
     */
    function GetArray():array{return $this->data;}

    /**
     * Gets the JSON string that describes this ACHV object.
     * @return string The result JSON string.
     */
    function GetJson():string{return json_encode($this->data,JSON_FORCE_OBJECT);}

}