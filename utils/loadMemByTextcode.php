<?php
function loadMemByTextcode(Shmop $shmop, string $textcode):array{
    $dirEnd =hexdec(bin2hex(shmop_read($shmop, 0, 2)));
    $dirSz = $dirEnd-4;
    $hashSz = hexdec(bin2hex(shmop_read($shmop, 2, 1)));
    $offsetSz = hexdec(bin2hex(shmop_read($shmop, 3, 1)));
    $tupleSz = $hashSz + $offsetSz*2;

    $hash = substr(sha1($textcode),0,$hashSz*2);
    $primeIndex = (hexdec($hash)% ($dirSz/$tupleSz))*$tupleSz;
    $initIndex = $primeIndex+4;
    while($hash!== bin2hex(shmop_read($shmop,$primeIndex+4,$hashSz))){
        $primeIndex+=$tupleSz;
        if($primeIndex>=$dirSz)$primeIndex-=$dirSz;
        if($primeIndex==$initIndex)
            throw new InvalidArgumentException("Unable to find any text with the textcode \"$textcode\"");
    }
    $offset = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz+4,$offsetSz)));
    $size = hexdec(bin2hex(shmop_read($shmop,$primeIndex+$hashSz+4+$offsetSz,$offsetSz)));
    return [$offset, $size];
}
