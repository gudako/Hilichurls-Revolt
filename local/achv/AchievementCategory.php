<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

class AchievementCategory extends AchievementSystem
{
    public function __construct()
    {
        parent::__construct(...func_get_args());
        if(!$this->isCategory)throw new \TypeError("Constructing an ACHV category with a textcode of a item.");
    }

    public function Next(): AchievementCategory|false
    {
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc+self::$prefixOffsetSize, self::$prefixOffsetSize)));
        $loc = $this->memloc + self::$prefixOffsetSize + $itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc + self::$prefixOffsetSize, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchievementCategory($loc,$size+self::$prefixOffsetSize);
    }

    public function GetFirstItem(): AchievementItem|false{
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc+self::$prefixOffsetSize*2, self::$prefixOffsetSize)));
        $loc = $this->memloc + self::$prefixOffsetSize*2+$itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc + self::$prefixOffsetSize, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchievementItem($loc,$size);
    }
}