<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
use Game\Config, Game\Database;

//get database candidates
$config = new Config();
$database = new Database();
$db_username = $config->GetDBUsername();
$db_password = $config->GetDBPassword();

//check for session match
$authorized = $config->IsDebug() || (isset($_SESSION['username']) && $_SESSION['username'] === $db_username &&
    isset($_SESSION['password_hash']) && $_SESSION['password_hash'] === sha1($db_password));

// DEFINE CONSTANT AND FUNCTIONS FOR EASE
const colorError = 'darkred',  colorMaintain = 'blue', colorSuccess = 'darkgreen', colorProcess = 'darkslategray',
    colorSubprocess = 'gray';
const permissionFull = 0666;
const clickHereToContinue = "<a style='display: block;' href='server.php'>Click here to continue</a>".PHP_EOL;

//maintenance mode initialized?
$shmop_maintain = shmop_open($config->GetShmopIdMaintenance(), 'w', permissionFull, 26);
$maintain_initialized = $shmop_maintain !== false;
$shmop_lang = shmop_open($config->GetShmopIdLang(), 'w', permissionFull, $config->GetLangMaxSize());
$lang_initialized = $shmop_lang !== false;
$database_initialized = $database->IsInitialized();

// PREDEFINED FUNCTIONS FOR EASE!
$colortxt = function(string $text, string $color = 'black'):string{
    return "<p style='color:".$color.";'>".$text."</p>";
};

//- POST QUERY BEGIN -//
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    // - PREDEFINED FUNCTIONS FOR EASE! - //
    $checkMaintText = function(string $text)use($config):bool{
        foreach ($config->GetAllLanguages() as $lang){
            $pattern = "/(?<=<".$lang.">)(.|\r|\n)+(?=<\/".$lang.">)/";
            if(preg_match_all($pattern, $text)!==1) return false;
        }
        return true;
    };

    $mustInitMaint = function()use($maintain_initialized,$colortxt):void{
        if(!$maintain_initialized){
            echo $colortxt("Maintenance data is not initialized.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
    };

    //- WHEN USER WANT TO GET ACCESS TO THE ADMIN PANEL -//
    if(isset($_POST['username']) && $_POST['username'] === $db_username && isset($_POST['password']) &&
        $_POST['password'] === $db_password){
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password_hash'] = sha1($_POST['password']);
        echo $colortxt("Successfully logged into the server panel.",colorSuccess).clickHereToContinue;
        http_response_code(202);
        die();
    }
    elseif(!$authorized) http_response_code(403);

    //- INITIALIZE OR RELOAD LANGUAGE DATA -//
    elseif (isset($_POST['init_lang']) && $_POST['init_lang']=='true'){
        $mustInitMaint();
        if($lang_initialized && !$config->InMaintenance()){
            echo $colortxt("Reloading language-text file is only possible in maintenance mode.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        startLangInit:
        echo $colortxt("Loading the language-text file into memory...",colorProcess);
        $shmop = shmop_open($config->GetShmopIdLang(), 'c', permissionFull, $config->GetLangMaxSize());
        $decoded = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/lang/lang.json"), true);
        $byte = 0;
        $remap = array();
        foreach ($decoded as $textcode => $textItem){
            $content = '';
            foreach ($textItem as $lang => $text) $content .= '<'.$lang.'>'.$text.'</'.$lang.'>';
            shmop_write($shmop, $content, $byte);
            if(isset($remap[$textcode]))
                echo $colortxt("WARNING: Duplicate of textcode \"".$textcode."\"",colorError);
            $size = strlen($content);
            $remap[$textcode] = [$byte, $size];
            $byte += $size;
        }
        echo $colortxt("Memory loaded to offset ".$byte,colorSubprocess).PHP_EOL;
        echo $colortxt("Remapping the function calls in the PHP files...",colorProcess).PHP_EOL;
        $remapInFiles = function(string $dir) use (&$remapInFiles, $colortxt, $remap):void{
            $paths = scandir($dir);
            foreach ($paths as $path) {
                if(preg_match("/^\.\w+$/", $path)) continue;
                if(in_array(basename($path), ['.','..','css','vendor'])) continue;
                $path = realpath($dir.'/'.$path);
                if(is_dir($path)){
                    $remapInFiles($path);
                }
                else{
                    if(strtolower(pathinfo($path, PATHINFO_EXTENSION))!=='php') continue;
                    $lines = file($path);
                    if($lines === false){
                        echo $colortxt("WARNING: Failed to load file for read and write: \"".$path."\"",
                                colorError).PHP_EOL;
                        continue;
                    }
                    $linecnt = 0;
                    $dealcnt = 0;
                    for ($i=0;$i<=count($lines)-1;$i++){
                        $pattern = /** @lang RegExp */
                            "/(\d+, *\d+|\d+|)( *\/\* *(<-)?REMAP%([a-z\d][a-z\d_]+[a-z\d]) *\*\/)/";
                        $replaced = preg_replace_callback($pattern,
                            function($matches)use($colortxt, $path, $remap, $linecnt, &$dealcnt)
                            {
                                $textcode = $matches[4];
                                if(!isset($remap[$textcode])){
                                    echo $colortxt("WARNING: Undefined textcode \"".$textcode.
                                        "\" demanded in file: \"".$path."\" at line ".$linecnt,colorError).PHP_EOL;
                                    return $matches[0];
                                }
                                $dealcnt++;
                                return $remap[$textcode][0].','.$remap[$textcode][1].$matches[2];
                            },$lines[$i]);
                        $lines[$i] = $replaced;
                        $linecnt++;
                    }
                    if($dealcnt==0) continue;
                    file_put_contents($path, implode('', $lines));
                    echo $colortxt("Made ".$dealcnt." replacements in file: \"".$path."\"",colorSubprocess).
                        PHP_EOL;
                }
            }
        };
        $remapInFiles($_SERVER['DOCUMENT_ROOT']);
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    //- INITIALIZE OR RELOAD MAINTENANCE DATA -//
    elseif (isset($_POST['init_maintain']) && $_POST['init_maintain']=='true'){
        if($maintain_initialized && !$config->InMaintenance()){
            echo $colortxt("ERROR: No maintenance is currently in progress.",colorError).PHP_EOL;
            if(!$config->IsDebug()){
                echo clickHereToContinue;
                http_response_code(400);
                die();
            }
            else echo $colortxt("The previous error is suppressed by DEBUG mode.",colorError).PHP_EOL;
        }
        $quitMaint = $maintain_initialized && !$config->NoMaintenance();
        echo $colortxt("Loading initial maintenance status memory...",colorProcess);
        $shmop_maintain= shmop_open($config->GetShmopIdMaintenance(),'c',permissionFull,26);
        shmop_write($shmop_maintain, '00000000000000000000000000', 0);
        if($quitMaint){
            echo $colortxt("Successfully reset maintenance memory. Requiring a language-text memory reload.",
                colorSubprocess).PHP_EOL;
            goto startLangInit;
        }
        else echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    //- START A MAINTENANCE with TEXT AND TIME -//
    elseif(isset($_POST['maintain_hrs'], $_POST['maintain_txt'])){
        if(!$maintain_initialized || !$lang_initialized){
            echo $colortxt("Make sure everything is initialized well.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!$config->NoMaintenance()){
            echo $colortxt("Already under maintenance or already issued a maintenance.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        $maintHrs = $_POST['maintain_hrs'];
        $maintText = $_POST['maintain_txt'];
        if (filter_var($maintHrs, FILTER_VALIDATE_INT)===false ||
            $maintHrs < ($config->IsDebug()?0:1) || $maintHrs > 24){
            echo $colortxt("The maintain number input is not valid.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!$checkMaintText($maintText)){
            echo $colortxt("ERROR: Make sure your text is available in all languages without duplicate.",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo $colortxt("Issuing a maintenance after ".$maintHrs." hours...");
        $now = new DateTime();
        $add = new DateInterval('PT'.$maintHrs.'H');
        $toWrite = $now->add($add)->format('Y-m-d H:i:s.u');
        shmop_write($shmop_maintain, $toWrite, 0);
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt", $maintText);
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    //- CHANGE THE MAINTENANCE TEXT -//
    elseif(isset($_POST['change_maintain_text'])){
        if(!$maintain_initialized){
            echo $colortxt("Maintenance data uninitialized.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if($config->NoMaintenance()){
            echo $colortxt("Currently no maintenance is issued.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!$checkMaintText($_POST['change_maintain_text'])){
            echo $colortxt("Make sure your text is available in all languages and ...without duplicate!",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo $colortxt("Changing maintenance text...");
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt", $_POST['change_maintain_text']);
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    // CREATE THE DATABASE, THAT IS INITIALIZATION
    elseif(isset($_POST['db_init'])&&$_POST['db_init']=='true'){
        if(!$maintain_initialized){
            echo $colortxt("Maintenance data uninitialized. You should always do that first!",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo $colortxt("Running initialization database scripts...").PHP_EOL;
        $initData = file_get_contents($_SERVER['DOCUMENT_ROOT']."/db/db_init.sql");
        echo $initData;
        $res = $database->GetConnection(false)->query($initData);
        if ($res!==false) echo $colortxt("Success!",colorSuccess).clickHereToContinue;
        else echo $colortxt("Things are going wrong. Need debug.",colorError).clickHereToContinue;
    }
    else{
        echo clickHereToContinue;
        http_response_code(200);
    }

    http_response_code(202);
    die();
}
// POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END --- POST QUERY END //

elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_session_id']) && $_GET['get_session_id'] === 'true'){
    echo 'session_id='. session_id() . PHP_EOL;
    http_response_code(200);
    die();
}

if(!$authorized){
    http_response_code(403);
    die();
}

$postButton = function (string $call, string $text): void{
    echo "<form action='server.php' method='post'><input type='text' style='display: none;' name='".$call."' ".
        "value='true'><input type='submit' value='".$text."'></form>".PHP_EOL;
};

if(!$maintain_initialized){
    echo $colortxt("The maintenance data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_maintain', 'Click here to load it');
}
else{
    echo $colortxt("Maintenance data loaded fine.",colorSuccess).PHP_EOL;
    $maintainTime = $config->GetMaintenanceTime();

    if($maintainTime===false){
        $defMaintText = '';
        foreach ($config->GetAllLanguages() as $lang) $defMaintText .= "<".$lang."></".$lang.">".PHP_EOL;
        echo "<form action='server.php' method='post'>".
            "<label>Issue a maintanence for </label><input type='number' name='maintain_hrs' ".
            "value='4' max='24' min='" .($config->IsDebug()?'0':'1').
            "'><label> hours</label><br>with the message:<br>".
            "<textarea name='maintain_txt'>".$defMaintText."</textarea>".
            "<br><input type='submit' value='Issue maintenence'></form>";
    }
    else{
        if($maintainTime->invert == 0){
            echo $colortxt("Currently under MAINTENANCE state for ".
                    $maintainTime->format('%H:%I:%S'),colorMaintain).PHP_EOL;
        }
        elseif($maintainTime->invert == 1){
            echo $colortxt("Maintenance state will start after ".
                    $maintainTime->format('%H:%I:%S'),colorMaintain).PHP_EOL;
        }
        $data = file_get_contents('_maintenance.txt');
        echo "<form action='server.php' method='post'><label>Current maintenance text:</label>".
            "<textarea name='change_maintain_text'>".($data===false?'<en></en>\n<zh></zh>':$data).
            "</textarea><br>>>>>>>>>>>>>>>>>>>>>><input type='submit' value='Set maintenance text'></form>";
        if($data===false) echo $colortxt("WARNING: Currently NO maintenance text is set.",colorError);
        if($maintainTime->invert == 0) $postButton('init_maintain', 'Exit maintenance mode');
        else if($config->IsDebug()) $postButton('init_maintain', 'Exit maintenance mode (DEBUG ONLY)');
    }
}

if(!$lang_initialized){
    echo $colortxt("The language file data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_lang', 'Click here to load it');
}
else{
    echo $colortxt("Language file loaded fine.",colorSuccess).PHP_EOL;
    if($config->InMaintenance()){
        $postButton('init_lang', 'Reload lang data');
    }
}

if($database_initialized){
    echo $colortxt("Database is loaded successfully.",colorSuccess).PHP_EOL;
    if(!$config->InMaintenance()) {
        echo $colortxt("WARNING: ONLY MODIFY THE DATABASE AT MAINTENANCE MODE!!!").PHP_EOL;
        echo $colortxt("DO NOT DO ANYTHING TO THE DATABASE NOW!! (except debug or setup)").PHP_EOL;
    }
    else{
        echo $colortxt("Changes about database you should better refer to phpMyAdmin etc...").PHP_EOL;
    }
}
else{
    echo $colortxt("Database is not properly set up...",colorError).PHP_EOL;
    $postButton('db_init', "Initialize Database!");
}

echo PHP_EOL;


