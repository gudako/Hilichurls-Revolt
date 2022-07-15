<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

class AchievementCategory extends AchievementSystem
{
    public function __construct()
    {
        parent::__construct();
        $args = func_get_args();

    }
}