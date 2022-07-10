<?php
include_once 'config.php';
session_start();

$config = new Config();
if(!$config->IsDebug() &&
    (!isset($_SESSION['username']) || $_SESSION['username'] !== $config->GetDBUsername() ||
    !isset($_SESSION['password']) || $_SESSION['password'] !== $config->GetDBPassword())){
    http_response_code(403);
    die();
}
echo "Loading language file into memory...";
initializeLangMemory();
header("Location: ../server.php");
die();

function initializeLangMemory(){
    $shmop = shmop_open(1, 'c', 0600, 100000);
    $scriptContent = '<?php'.PHP_EOL.'$GLOBALS[\'langtab\']='.PHP_EOL.'array('.PHP_EOL;

    $langObj = json_decode(file_get_contents(__DIR__."/lang/lang.json"), true);
    $byteIndex = 0;
    foreach ($langObj as $textcode => $langItem){
        $content = '';
        foreach ($langItem as $langName => $value)
            $content .= '<'.$langName.'>'.$value.'</'.$langName.'>';
        $textcode_hash = substr(sha1($textcode), 0, 10);
        shmop_write($shmop, $content, $byteIndex);
        $size = strlen($content);
        $scriptContent .='"'.$textcode_hash.'"=>['.$byteIndex.','.$size.'],'.PHP_EOL;
        $byteIndex += $size;
    }
    $scriptContent .= '"0000000000"=>['.$byteIndex.']);';
    file_put_contents('lang\_langtab.php', $scriptContent);
}
