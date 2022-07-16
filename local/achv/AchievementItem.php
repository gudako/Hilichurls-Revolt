<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

class AchievementItem extends AchievementSystem
{
    public function __construct()
    {
        parent::__construct(...func_get_args());
        if($this->isCategory)throw new \TypeError("Constructing an ACHV item with a textcode of a category.");
    }

    public function Next():AchievementItem|false{
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc, self::$prefixOffsetSize)));
        $loc = $this->memloc + $itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchievementItem($loc,$size);
    }

    public function GetDescription(): string
    {
        if(!isset($this->data['name']))throw new \Error("The target data has no key named \"name\".",ERROR_HIGHLIGHTED);
        return memtxt("achv_desc_".$this->data['name']);
    }
}