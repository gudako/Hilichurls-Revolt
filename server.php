<?php
require_once 'config.php';
require_once 'script/database.php';
use Game\Config, Game\Database;

session_start();

//get database candidates
$config = new Config();
$database = new Database();
$db_username = $config->GetDBUsername();
$db_password = $config->GetDBPassword();

//check for session match
$authorized = isset($_SESSION['username']) && $_SESSION['username'] === $db_username &&
    isset($_SESSION['password_hash']) && $_SESSION['password_hash'] === sha1($db_password);

// DEFINE CONSTANT AND FUNCTIONS FOR EASE
const colorError = 'darkred';
const colorMaintain = 'blue';
const colorSuccess = 'darkgreen';
const permissionFull = 0666;
const clickHereToContinue =
    "<a style='display: block;' href='server.php'>Click here to continue</a>" . PHP_EOL;
Function ColoredText(string $text, string $color = 'black'):string{
    return "<p style='color:".$color.";'>".$text."</p>";
}

//maintenance mode initialized?
$shmop_maintain = shmop_open($config->GetShmopIdMaintenance(), 'w', permissionFull, 26);
$maintain_initialized = $shmop_maintain !== false;
$shmop_lang = shmop_open($config->GetShmopIdLang(), 'w', permissionFull, $config->GetLangMaxSize());
$lang_initialized = $shmop_lang !== false && file_exists($_SERVER['DOCUMENT_ROOT']."/lang/_langtab.php");
$database_initialized = $database->IsInitialized();

// PREDEFINED FUNCTIONS FOR EASE!
function GetDefaultMaintenanceText(Config $config):string{
    $str = '';
    foreach ($config->GetAllLanguages() as $lang){
        $str .= "<".$lang."></".$lang.">".PHP_EOL;
    }
    return $str;
}

function CheckMaintenanceText(string $text, Config $config):bool{
    foreach ($config->GetAllLanguages() as $lang){
        $pattern = "/(?<=<".$lang.">)(.|\r|\n)+(?=<\/".$lang.">)/";
        if(preg_match_all($pattern, $text)!==1) return false;
    }
    return true;
}

// POST QUERY BEGIN --- POST QUERY BEGIN --- POST QUERY BEGIN --- POST QUERY BEGIN --- POST QUERY BEGIN //
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    //- WHEN USER WANT TO GET ACCESS TO THE ADMIN PANEL -//
    if(isset($_POST['username']) && $_POST['username'] === $db_username &&
        isset($_POST['password']) && $_POST['password'] === $db_password){
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password_hash'] = sha1($_POST['password']);
        echo "<p>Successfully logged into the admin panel.</p>".clickHereToContinue;
        http_response_code(202);
    }
    elseif(!$config->IsDebug() && !$authorized){
        http_response_code(403);
    }

    //- INITIALIZE OR RELOAD LANGUAGE DATA -//
    elseif (isset($_POST['init_lang']) && $_POST['init_lang']=='true'){
        if(!$maintain_initialized){
            echo ColoredText("Maintenance data is not initialized.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        elseif($lang_initialized && !$config->InMaintenance()){
            echo ColoredText("Can only reload language file in maintenance mode.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo ColoredText("Loading language file into memory...");
        $shmop = shmop_open($config->GetShmopIdLang(), 'c', permissionFull, $config->GetLangMaxSize());
        $scriptContent = '<?php'.PHP_EOL.'$GLOBALS[\'langtab\']='.PHP_EOL.'array('.PHP_EOL;
        $langObj = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/lang/lang.json"), true);
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
        echo ColoredText("Success!",colorSuccess).clickHereToContinue;
    }

    //- INITIALIZE OR RELOAD MAINTENANCE DATA -//
    elseif (isset($_POST['init_maintain']) && $_POST['init_maintain']=='true'){
        if($maintain_initialized && !$config->InMaintenance()){
            echo ColoredText("Only stop a maintenance when it is in progress.",colorError).PHP_EOL;
            if(!$config->IsDebug()){
                echo clickHereToContinue;
                http_response_code(400);
                die();
            }
        }
        if($config->InMaintenance()){
            echo ColoredText("Maintenance is quitting. Remember to initialize the lang file again.");
            shmop_delete($shmop_lang);
            unlink($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt");
        }
        echo ColoredText("Loading maintenance memory to 'No Maintenance'...");
        unlink($_SERVER['DOCUMENT_ROOT']."/lang/_langtab.php");
        shmop_delete($shmop_lang);
        $shmop_maintain= shmop_open($config->GetShmopIdMaintenance(),'c',permissionFull,26);
        shmop_write($shmop_maintain, '00000000000000000000000000', 0);
        echo ColoredText("Success!",colorSuccess).clickHereToContinue;
    }

    //- START A MAINTENANCE with TEXT AND TIME -//
    elseif(isset($_POST['maintain_hrs'], $_POST['maintain_txt'])){
        if(!$maintain_initialized || !$lang_initialized){
            echo ColoredText("Make sure everything is initialized well.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!$config->NoMaintenance()){
            echo ColoredText("Already under maintenance or already issued a maintenance.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        $maintainHrs = $_POST['maintain_hrs'];
        $maintainTxt = $_POST['maintain_txt'];
        if (filter_var($maintainHrs, FILTER_VALIDATE_INT)===false ||
            $maintainHrs < ($config->IsDebug()?0:1) || $maintainHrs > 24){
            echo ColoredText("The maintain number input is not valid.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!CheckMaintenanceText($maintainTxt, $config)){
            echo ColoredText("Make sure your text is available in all languages without duplicate.",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo ColoredText("Issuing a maintenance after ".$maintainHrs." hours...");
        $now = new DateTime();
        $add = new DateInterval('PT'.$maintainHrs.'H');
        $toWrite = $now->add($add)->format('Y-m-d H:i:s.u');
        shmop_write($shmop_maintain, $toWrite, 0);
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt", $maintainTxt);
        echo ColoredText("Success!",colorSuccess).clickHereToContinue;
    }

    //- CHANGE THE MAINTENANCE TEXT -//
    elseif(isset($_POST['change_maintain_text'])){
        if(!$maintain_initialized){
            echo ColoredText("Maintenance data uninitialized.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if($config->NoMaintenance()){
            echo ColoredText("Currently no maintenance is issued.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!CheckMaintenanceText($_POST['change_maintain_text'],$config)){
            echo ColoredText("Make sure your text is available in all languages and ...without duplicate!",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo ColoredText("Changing maintenance text...");
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt", $_POST['change_maintain_text']);
        echo ColoredText("Success!",colorSuccess).clickHereToContinue;
    }

    // CREATE THE DATABASE, THAT IS INITIALIZATION
    elseif(isset($_POST['db_init'])&&$_POST['db_init']=='true'){
        if(!$maintain_initialized){
            echo ColoredText("Maintenance data uninitialized. You should always do that first!",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo ColoredText("Running initialization database scripts...").PHP_EOL;
        $initData = file_get_contents($_SERVER['DOCUMENT_ROOT']."/script/db_init.sql");
        echo $initData;
        $res = $database->GetConnection(false)->query($initData);
        if ($res!==false) echo ColoredText("Success!",colorSuccess).clickHereToContinue;
        else echo ColoredText("Things are going wrong. Need debug.",colorError).clickHereToContinue;
    }
    else{
        echo clickHereToContinue;
        http_response_code(200);
    }
    die();
}
// POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END //

elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_session_id']) && $_GET['get_session_id'] === 'true'){
    echo 'session_id='. session_id() . PHP_EOL;
    http_response_code(200);
    die();
}

if(!$config->IsDebug() && !$authorized){
    http_response_code(403);
    die();
}

function InsertDirectPostButton(string $call, string $text): void{
    echo "<form action='server.php' method='post'><input type='text' style='display: none;' name='".$call."' ".
        "value='true'><input type='submit' value='".$text."'></form>".PHP_EOL;
}

if(!$maintain_initialized){
    echo ColoredText("The maintenance data hasn't been loaded into memory.",colorError).PHP_EOL;
    InsertDirectPostButton('init_maintain', 'Click here to load it');
}
else{
    echo ColoredText("Maintenance data loaded fine.",colorSuccess).PHP_EOL;
    $maintainTime = $config->GetMaintenanceTime();

    function InsertMaintenanceTextForm():void{
        $data = file_get_contents('_maintenance.txt');
        echo "<form action='server.php' method='post'><label>Current maintenance text:</label>".
            "<textarea name='change_maintain_text'>".($data===false?'<en></en>\n<zh></zh>':$data).
            "</textarea><br>>>>>>>>>>>>>>>>>>>>>><input type='submit' value='Set maintenance text'></form>";
        if($data===false)
            echo ColoredText("Currently NO maintenance text is set. Make sure to set one now!",colorError);
    }

    if($maintainTime===false){
        echo "<form action='server.php' method='post'>".
            "<label>Issue a maintanence for </label><input type='number' name='maintain_hrs' ".
            "value='4' max='24' min='" .($config->IsDebug()?'0':'1').
            "'><label> hours</label><br>with the message:<br>".
            "<textarea name='maintain_txt'>".GetDefaultMaintenanceText($config)."</textarea>".
            "<br><input type='submit' value='Issue maintenence'></form>".PHP_EOL;
    }
    elseif($maintainTime->invert == 0){
        echo ColoredText("Currently under MAINTENANCE state for ".
            $maintainTime->format('%H:%I:%S'),colorMaintain).PHP_EOL;
        InsertMaintenanceTextForm();
        InsertDirectPostButton('init_maintain', 'Exit maintenance mode');
    }
    elseif($maintainTime->invert == 1){
        echo ColoredText("Maintenance state will start after ".
            $maintainTime->format('%H:%I:%S'),colorMaintain).PHP_EOL;
        InsertMaintenanceTextForm();
        if($config->IsDebug())
            InsertDirectPostButton('init_maintain', 'Exit maintenance mode (DEBUG ONLY)');
    }
}

if(!$lang_initialized){
    echo ColoredText("The language file data hasn't been loaded into memory.",colorError).PHP_EOL;
    InsertDirectPostButton('init_lang', 'Click here to load it');
}
else{
    echo ColoredText("Language file loaded fine.",colorSuccess).PHP_EOL;
    if($config->InMaintenance()){
        InsertDirectPostButton('init_lang', 'Reload lang data');
    }
}

if($database_initialized){
    echo ColoredText("Database is loaded successfully.",colorSuccess).PHP_EOL;
    if(!$config->InMaintenance()) {
        echo ColoredText("WARNING: ONLY MODIFY THE DATABASE AT MAINTENANCE MODE!!!").PHP_EOL;
        echo ColoredText("DO NOT DO ANYTHING TO THE DATABASE NOW!! (except debug or setup)").PHP_EOL;
    }
    else{
        echo ColoredText("Changes about database you should better refer to phpMyAdmin etc...").PHP_EOL;
    }
}
else{
    echo ColoredText("Database is not properly set up...",colorError).PHP_EOL;
    InsertDirectPostButton('db_init', "Initialize Database!");
}

echo PHP_EOL;


