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
const colorError = 'darkred', colorMaintain = 'blue', colorSuccess = 'darkgreen', colorProcess = 'darkslategray',
    colorSubprocess = 'gray';
const permissionFull = 0666;
const clickHereToContinue = "<a style='display: block;' href='server.php'>Click here to continue</a>".PHP_EOL;

//maintenance mode initialized?
$shmop_maint = shmop_open($config->GetShmopIdMaintenance(), 'w', permissionFull, 26);
$maint_initialized = $shmop_maint !== false;
$shmop_lang = shmop_open($config->GetShmopIdLang(), 'w', permissionFull, $config->GetLangMaxSize());
$lang_initialized = $shmop_lang !== false;
$db_initialized = $database->IsInitialized();

// PREDEFINED FUNCTIONS FOR EASE!
$colortxt = function(string $text, string $color = 'black'):string{
    return "<p style='color:".$color.";'>".$text."</p>";
};

//- POST QUERY BEGIN -//
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    // - PREDEFINED FUNCTIONS FOR EASE! - //
    $mustInitMaint = function()use($maint_initialized,$colortxt):void{
        if(!$maint_initialized){
            echo $colortxt("ERROR: Maintenance data is not initialized.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
    };

    $checkMaintText = function(string $text)use($config):bool{
        foreach ($config->GetAllLanguages() as $lang){
            $pattern = "/<".$lang.">[\s\S]*\S+[\s\S]*<\/".$lang.">/u";
            if(preg_match_all($pattern, $text)!==1) return false;
        }
        return true;
    };
    
    $mustHaveGoodMaintText = function($postName)use($checkMaintText,$colortxt):void{
        if(!$checkMaintText($_POST[$postName])){
            echo $colortxt("ERROR: Make sure your text is available in all languages and without duplicate.",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();}
    };

    $simplifyMaintText=function ($maintText)use($config): string {
        $langs =implode("|",$config->GetAllLanguages());
        $optimized1 = preg_replace("/<(".$langs.")>\s*([\s\S]+?)\s*<\/(".$langs.")>/u",
            "<$1>\n$2\n</$1>\n\n", $maintText);
        return preg_replace("/(\r\n|\n)(\r\n|\n)/u",'',$optimized1);
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
        if($lang_initialized && !$config->InMaintenance() && !$config->IsDebug()){
            echo $colortxt("Reloading language-text file is only possible in maintenance mode.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        startLangInit:
        echo $colortxt("Loading the language-text file into memory......",colorProcess);
        $shmop = shmop_open($config->GetShmopIdLang(), 'c', permissionFull, $config->GetLangMaxSize());
        $decoded = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT']."/lang/lang.json"), true);
        $byte = 0;
        $remap = array();
        foreach ($decoded as $textcode => $textItem){
            $content = '';
            foreach ($textItem as $lang => $text) $content .= '<'.$lang.'>'.$text.'</'.$lang.'>';
            $size = strlen($content);
            if($byte+$size>=$config->GetLangMaxSize()){
                echo $colortxt("ERROR: Language-text data exceeding the max memory ". $config->GetLangMaxSize().
                    " bytes.",colorError).clickHereToContinue;
                http_response_code(400);
                die();
            }
            shmop_write($shmop, $content, $byte);
            if(isset($remap[$textcode]))
                echo $colortxt("WARNING: Duplicate of textcode \"".$textcode."\"",colorError);
            $remap[$textcode] = [$byte, $size];
            $byte += $size;
        }
        $maxSize = $config->GetLangMaxSize();
        echo $colortxt("Memory loaded to offset ".$byte." for a maximum at ".$maxSize.
                " (space used ".round(100.0*$byte/$maxSize,2)."%).", colorSubprocess).PHP_EOL;
        echo $colortxt("Remapping the function calls in the PHP files......",colorProcess).PHP_EOL;
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
                            "/(\d+, *\d+|\d+|)( *\/\* *(<-)?REMAP%([a-z\d][a-z\d_]+[a-z\d\?\!]) *\*\/)/";
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
    elseif (isset($_POST['init_maint']) && $_POST['init_maint']=='true'){
        if($maint_initialized && !$config->InMaintenance() &&!$config->IsDebug()){
            echo $colortxt("ERROR: No maintenance is currently in progress.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        $quitMaint = $maint_initialized && !$config->NoMaintenance();
        echo $colortxt("Loading initial maintenance status memory......",colorProcess);
        $shmop_maint= shmop_open($config->GetShmopIdMaintenance(),'c',permissionFull,26);
        shmop_write($shmop_maint, '00000000000000000000000000', 0);
        if($quitMaint){
            echo $colortxt("Successfully reset maintenance memory. Requiring a language-text memory reload.",
                colorSubprocess).PHP_EOL;
            goto startLangInit;
        }
        else echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    //- START A MAINTENANCE with TEXT AND TIME -//
    elseif(isset($_POST['maint_hrs'], $_POST['maint_mins'], $_POST['maint_text'])){
        if(!$maint_initialized || !$lang_initialized || !$db_initialized){
            echo $colortxt("ERROR: Make sure everything is initialized well.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        if(!$config->NoMaintenance()){
            echo $colortxt("ERROR: Already under maintenance or already issued a maintenance.",colorError).
                clickHereToContinue;
            http_response_code(400);
            die();
        }
        $maintHrs = $_POST['maint_hrs'];
        $maintMins = $_POST['maint_mins'];
        if (filter_var($maintHrs, FILTER_VALIDATE_INT)===false || $maintHrs>24 || $maintHrs<0 ||
            filter_var($maintMins, FILTER_VALIDATE_INT)===false || $maintMins>59 || $maintMins<0 ||
            ($maintHrs==0&&$maintMins==0)){
            echo $colortxt("ERROR: The maintain time number input is invalid.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        $mustHaveGoodMaintText('maint_text');
        echo $colortxt("Issuing a maintenance after ".$maintHrs." hours ". $maintMins." minutes......",
                colorProcess).PHP_EOL;
        $now = new DateTime();
        $add = new DateInterval('PT'.$maintHrs.'H'.$maintMins.'M');
        $toWrite = $now->add($add)->format('Y-m-d H:i:s.u');
        shmop_write($shmop_maint, $toWrite, 0);
        echo $colortxt("Written to the maintenance memory: \"".$toWrite."\"",colorSubprocess).PHP_EOL;
        $_POST['change_maint_text'] = $_POST['maint_text'];
        goto startChangeMaintText;
    }

    //- CHANGE THE MAINTENANCE TEXT -//
    elseif(isset($_POST['change_maint_text'])){
        $mustInitMaint();
        if($config->NoMaintenance()){
            echo $colortxt("ERROR: Currently no maintenance is issued.",colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        $mustHaveGoodMaintText('change_maint_text');
        startChangeMaintText:
        $targetText =$simplifyMaintText($_POST['change_maint_text']);
        echo $colortxt("Changing maintenance text to......",colorProcess);
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maint.txt", $targetText);
        echo $colortxt(str_replace(["\r\n","\n"],"<br>",htmlspecialchars($targetText)),colorSubprocess).PHP_EOL;
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    // CREATE THE DATABASE, THAT IS INITIALIZATION
    elseif(isset($_POST['db_init'])&&$_POST['db_init']=='true'){
        $mustInitMaint();
        echo $colortxt("Running initialization database scripts......",colorProcess).PHP_EOL;
        $initData = file_get_contents($_SERVER['DOCUMENT_ROOT']."/db/db_init.sql");
        echo $colortxt($initData,colorSubprocess).PHP_EOL;
        $res = $database->GetConnection(false)->query($initData);
        if ($res!==false) echo $colortxt("Success!",colorSuccess).clickHereToContinue;
        else echo $colortxt("ERROR: Things are going wrong. Need debug.",colorError).clickHereToContinue;
    }

    //ONLY FOR DEBUG, IMMEDIATELY DROP INTO THE MAINTENANCE MODE
    elseif(isset($_POST['imm_maint'])&&$_POST['imm_maint']=='true'){
        $mustInitMaint();
        if(!$config->IsDebug()){
            echo $colortxt("ERROR: Only available in DEBUG mode to immediately turn into maintenance.",
                    colorError).clickHereToContinue;
            http_response_code(400);
            die();
        }
        echo $colortxt("Issuing an immediate maintenance......",colorProcess).PHP_EOL;
        $toWrite=(new DateTime())->format('Y-m-d H:i:s.u');
        shmop_write($shmop_maint, $toWrite, 0);
        echo $colortxt("Written to the maintenance memory: \"".$toWrite."\"",colorSubprocess).PHP_EOL;
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }
    
    else{
        echo clickHereToContinue;
        http_response_code(200);
        die();
    }
    http_response_code(202);
    die();
}
// POST QUERY END //

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

if(!$maint_initialized){
    echo $colortxt("ERROR: The maintenance data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_maint', 'Click here to load it');
}
else{
    echo $colortxt("Maintenance data loaded fine.",colorSuccess).PHP_EOL;
    $maintainTime = $config->GetMaintenanceTime();

    $getDefMaintText=function()use($config):string{
        $defMaintText = '';
        foreach ($config->GetAllLanguages() as $lang)
            $defMaintText .= "<".$lang.">".PHP_EOL.($config->IsDebug()?"TEST VALUE":"").PHP_EOL. "</".$lang.">".PHP_EOL;
        return $defMaintText;
    };

    if($maintainTime===false){
        echo "<form action='server.php' method='post'>".
            "Issue a maintanence for <input type='number' name='maint_hrs' value='1' max='24' min='0'> hours ".
            "<input type='number' name='maint_mins' value='0' max='59' min='0'> minutes <br>with the message:<br>".
            "<textarea name='maint_text' style='height: 255px; width: 500px; resize: none;'>".$getDefMaintText().
            "</textarea><br><input type='submit' value='Issue a Maintenence'></form>";
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
        $maintFileData = file_get_contents('_maint.txt');
        echo "<form action='server.php' method='post'><label>Current maintenance text:</label><br>".
            "<textarea name='change_maint_text' style='height: 255px; width: 500px; resize: none;'>".
            ($maintFileData===false?$getDefMaintText():$maintFileData).
            "</textarea><br><input type='submit' value='Change Maintenance Text'></form>";
        if($maintFileData===false) echo $colortxt("WARNING: Currently NO maintenance text is set.",colorError);
        if($maintainTime->invert == 0) $postButton('init_maint', 'Exit Maintenance Mode');
        elseif($config->IsDebug()) {
            $postButton('imm_maint', 'Immediately enter Maintenance (DEBUG ONLY)');
            $postButton('init_maint', 'Undo issuing the Maintenance (DEBUG ONLY)');
        }
    }
}

if(!$lang_initialized){
    echo $colortxt("The language file data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_lang', 'Click here to load language-text memory');
}
else{
    echo $colortxt("Language-text memory loaded fine.",colorSuccess).PHP_EOL;
    if($config->InMaintenance()) $postButton('init_lang', 'Reload language-text memory');
    elseif ($config->IsDebug()) $postButton('init_lang', 'Reload language-text memory (DEBUG ONLY)');
}

if($db_initialized){
    echo $colortxt("Database is loaded successfully.",colorSuccess).PHP_EOL;
    if(!$config->InMaintenance() && !$config->IsDebug())
        echo $colortxt("WARNING: ONLY MODIFY THE DATABASE AT MAINTENANCE MODE!!!").PHP_EOL;
    else echo $colortxt($config->IsDebug()?
            "Debugging the database you'd refer to Intellij IDEA...... You know that all!":
            "Changes about database you should better refer to phpMyAdmin, etc......").PHP_EOL;
}
else{
    echo $colortxt("Database is not properly set up......",colorError).PHP_EOL;
    $postButton('db_init', "Initialize the Database!");
}

echo PHP_EOL;


