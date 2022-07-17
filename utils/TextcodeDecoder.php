<?php
namespace utils;
/**
 * A static class providing the method for getting a textcode's representing memory offset&size in a Shmop memory object.
 */
class TextcodeDecoder{
    /**
     * This is a static class. It cannot be constructed.
     */
    private function __construct() {}

    /**
     * From a Shmop memory built with hashtable, get the item's offset and size with its textcode.
     * @param \Shmop $shmop The Shmop memory object.
     * @param string $textcode The textcode to be searched.
     * @return array An array with two integers, first is the offset and the second is the size, of the textcode's item in the memory.
     */
    static function ParseByTextcode(\Shmop $shmop, string $textcode):array{
        $dirEnd =hexdec(bin2hex(shmop_read($shmop, 0, 2)));
        $dirSz = $dirEnd-4;
        $hashSz = hexdec(bin2hex(shmop_read($shmop, 2, 1)));
        $offsetSz = hexdec(bin2hex(shmop_read($shmop, 3, 1)));
        $tupleSz = $hashSz + $offsetSz*2;

        $hash = substr(sha1($textcode),0,$hashSz*2);
        $primeIndex = (hexdec($hash)% ($dirSz/$tupleSz))*$tupleSz+4;
        $initIndex = $primeIndex;
        while($hash!== bin2hex(shmop_read($shmop,$primeIndex,$hashSz))){
            $primeIndex+=$tupleSz;
            if($primeIndex>=$dirSz)$primeIndex-=$dirSz;
            if($primeIndex==$initIndex)
                throw new \InvalidArgumentException("Unable to find any text with the textcode \"$textcode\"");
        }
        $offset = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz,$offsetSz)));
        $size = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz+$offsetSz,$offsetSz)));
        return [$offset, $size];
    }
}
