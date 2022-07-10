<?php
include_once 'config.php';
session_start();

//get database candidates
$config = new Config();
$db_username = $config->GetDBUsername();
$db_password = $config->GetDBPassword();

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

if(!$config->IsDebug() && !$authorized){
    http_response_code(403);
    die();
}

$shmop = shmop_open(1, 'a', 0600, 100000);
$shmop_initialized = $shmop !== false;
if(!$shmop_initialized){
    echo "<p>The language file hasn't been loaded into memory.</p>".PHP_EOL;
    echo "<a href='server_call/init_lang.php'>Click here to initialize</a>".PHP_EOL;
}
else{
    echo "<p>Language file loaded fine.</p>".PHP_EOL;
}
echo PHP_EOL;


