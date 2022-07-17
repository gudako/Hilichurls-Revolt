<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

/**
 * Represents a memory part of an achievement item.
 */
final class AchvItem extends AchvObject
{
    /**
     * Construct a new achievement item.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        if($this->isCategory)throw new \TypeError("Constructing an \"ACHV item\" with a textcode of an \"ACHV category\" is not allowed.",EX_CODE_EXPECTED);
    }

    /**
     * Get the next ACHV item in memory.
     * @return AchvItem|false Returns false if no next item or has hit the border of the next category. Otherwise, return the next item.
     */
    public function Next():AchvItem|false{
        $itemSize = hexdec(bin2hex(shmop_read(self::$shmop, $this->memloc, self::$prefixOffsetSize)));
        $loc = $this->memloc + $itemSize;
        $size = hexdec(bin2hex(shmop_read(self::$shmop, $loc, self::$prefixOffsetSize)));
        if($size==0) return false;
        return new AchvItem($loc,$size);
    }

    /**
     * Get the readable description in current language of this ACHV item.
     * @return string The result description.
     */
    public function GetDescription(): string
    {
        if(!isset($this->data['name']))throw new \Error("The target data has no key named \"name\".",EX_CODE_IMPORTANT);
        return memtxt("achv_desc_".$this->data['name']);
    }
}