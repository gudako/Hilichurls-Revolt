<?php
session_start();

//get database candidates
$config = json_decode(file_get_contents("config.json"), true);
$db_username = $config['db_username'];
$db_password = $config['db_password'];

//check for session match
$authorized = isset($_SESSION['username']) && $_SESSION['username'] === $db_username &&
    isset($_SESSION['password']) && $_SESSION['password'] === $db_password;

//post query
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if ($authorized){
        echo "Already authorized.";
        http_response_code(202);
    }
    else if(isset($_POST['username']) && $_POST['username'] === $db_username &&
        isset($_POST['password']) && $_POST['password'] === $db_password){
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password'] = $_POST['password'];
        echo "Successfully logged into the admin panel.";
        http_response_code(202);
    }
    else{
        http_response_code(403);
    }
    die();
}
else if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_session_id']) && $_GET['get_session_id'] === 'true'){
    echo 'session_id='. session_id() . PHP_EOL;
    http_response_code(200);
    die();
}



$shmop = shmop_open(1, 'a', 0600, 100000);
$shmop_initialized = $shmop !== false;

initializeLangMemory();

function initializeLangMemory(){
    $shmop = shmop_open(1, 'c', 0600, 100000);
    $scriptContent = '<?php'.PHP_EOL.'$GLOBALS[\'langtab\']=array('.PHP_EOL;

    $langObj = json_decode(file_get_contents("lang/lang.json"), true);
    $byteIndex = 0;
    foreach ($langObj as $textcode => $langItem){
        $content = '';
        foreach ($langItem as $langName => $value)
            $content .= '<'.$langName.'>'.$value.'</'.$langName.'>';
        $textcode_hash = substr(sha1($textcode), 0, 10);
        shmop_write($shmop, $content, $byteIndex);
        $scriptContent .='"'.$textcode_hash.'"=>'.$byteIndex.','.PHP_EOL;
        $byteIndex += strlen($content);
    }
    $scriptContent .= '"0000000000"=>'.$byteIndex.');';
    file_put_contents('lang\_langtab.php', $scriptContent);

}

