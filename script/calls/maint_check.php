<?php
if(!isset($_SESSION)) session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
use Game\Config;

$config = new Config();
$maintainTime = $config->GetMaintenanceTime();
if($maintainTime===false){
    echo "false"; //means no maintenance or issues of
    die();
}
if($maintainTime->invert == 0){
    echo "true"; //means already started maintenance
    die();
}
echo $maintainTime->format('%h,%i'); //return the time remaining before maintain
die();
