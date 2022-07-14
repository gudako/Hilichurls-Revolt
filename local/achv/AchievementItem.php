<?php
namespace local\achv;
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

class AchievementItem extends AchievementSystem
{
    public function __construct(string $name)
    {
        parent::__construct();

    }
}