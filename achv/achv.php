<?php
namespace Game;
require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";
class Achievements
{
    private static array $achvdata, $visiachv, $catadata;

    function __construct(){
        if(!isset(self::$achvdata)){
            self::$achvdata = json_decode(file_get_contents('achv.json'),true);
            self::$visiachv = array();
            foreach (self::$achvdata as $achvname=>$achv){
                if($achv['visible']=='true') self::$visiachv[$achvname] = $achv;
            }
            self::$catadata = json_decode(file_get_contents('cata.json'), true);
        }
    }

    function GetAllVisibleAchv():array{
        return self::$visiachv;
    }

    function GetAchvByName(string $name):array{
        return self::$achvdata[$name];
    }

    function GetTotalScore():int{
        return self::$catadata['total']['point'];
    }

    function GetCategoryByName(string $name):array{
        return self::$catadata[$name];
    }
}