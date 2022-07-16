<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

$new1 = new \local\achv\AchievementItem('an_goodorbad');
$next = $new1->Next()->Next();
echo $next;

