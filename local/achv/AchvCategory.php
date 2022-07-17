<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

/**
 * Represents a memory part of an achievement category.
 */
final class AchvCategory extends AchvObject
{
    /**
     * Construct a new achievement category that can have items contained inside.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        if(!$this->isCategory)throw new \TypeError("Constructing an \"ACHV category\" with a textcode of an \"ACHV item\" is not allowed.",EX_CODE_EXPECTED);
    }

    public function Next(): AchvCategory|false
    {
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc+self::$prefixOffsetSize, self::$prefixOffsetSize)));
        $loc = $this->memloc + self::$prefixOffsetSize + $itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc + self::$prefixOffsetSize, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchvCategory($loc,$size+self::$prefixOffsetSize);
    }

    /**
     * Get the first item of the category.
     * @return AchvItem|false
     */
    public function GetFirstItem(): AchvItem|false{
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc+self::$prefixOffsetSize*2, self::$prefixOffsetSize)));
        $loc = $this->memloc + self::$prefixOffsetSize*2+$itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc + self::$prefixOffsetSize, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchvItem($loc,$size);
    }
}