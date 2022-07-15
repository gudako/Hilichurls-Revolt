<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/utils/AES-256-CBC.php";
use local\ConfigSystem;
use local\DatabaseSystem;

//get database candidates
$config = new ConfigSystem();
$database = new DatabaseSystem();
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
$shmop_lang = shmop_open($config->GetShmopIdLang(), 'w', permissionFull, $config->GetShmopLangMaxsz());
$lang_initialized = $shmop_lang !== false;
$shmop_achv = shmop_open($config->GetShmopIdAchv(), 'w', permissionFull, $config->GetShmopAchvMaxsz());
$achv_initialized = $shmop_achv !== false;
$db_initialized = $database->IsInitialized();

// PREDEFINED FUNCTIONS FOR EASE!
$colortxt = function(string $text, string $color = 'black'):string{
    return "<p style='color:$color;'>$text</p>";
};

$err = function(string $text)use($colortxt){
    echo $colortxt("ERROR: ".$text, colorError).clickHereToContinue.PHP_EOL;
    http_response_code(400);
    die();
};

$postButton = function (string $call, string $text): void{
    echo "<form action='server.php' method='post'><input type='text' style='display: none;' name='$call' ".
        "value='true'><input type='submit' value='$text'></form>".PHP_EOL;
};

//- POST QUERY BEGIN -//
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    // - PREDEFINED FUNCTIONS FOR EASE! - //
    $mustInitMaint = function()use($maint_initialized,$err):void{
        if(!$maint_initialized)$err("Maintenance data is not initialized.");
    };

    $checkMaintText = function(string $text)use($config):bool{
        foreach ($config->GetAllLanguages() as $lang){
            $pattern = "/<$lang>[\s\S]*\S+[\s\S]*<\/$lang>/u";
            if(preg_match_all($pattern, $text)!==1) return false;
        }
        return true;
    };
    
    $mustHaveGoodMaintText = function($postName)use($checkMaintText,$err):void{
        if(!$checkMaintText($_POST[$postName]))
            $err("Make sure your text is available in all languages and without duplicate.");
    };

    $simplifyMaintText=function ($maintText)use($config): string {
        $langs =implode("|",$config->GetAllLanguages());
        $optimized1 = preg_replace("/<($langs)>\s*([\s\S]+?)\s*<\/($langs)>/u",
            "<$1>\n$2\n</$1>\n\n", $maintText);
        return preg_replace("/(\r\n|\n)(\r\n|\n)/u",'',$optimized1);
    };

    $unsigned2bin=function (int $unsigned,int $bytes)use($err):string{
        $hex = dechex($unsigned);
        if(strlen($hex)>$bytes*2){
            $err("Cannot fit binary \"$hex\" into a $bytes bytes space.");
            http_response_code(400);
            die();
        }
        $hex = str_pad($hex,$bytes*2,'0',STR_PAD_LEFT);
        return hex2bin($hex);
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

    //- INITIALIZE OR RELOAD LANGUAGE OR ACHV DATA -//
    elseif ((isset($_POST['init_lang']) && $_POST['init_lang']=='true')||
            (isset($_POST['init_achv']) && $_POST['init_achv']=='true')){
        $mustInitMaint();
        $isAchv = isset($_POST['init_achv']) && $_POST['init_achv']=='true';
        if($lang_initialized && !$config->InMaintenance() && !$config->IsDebug())
            $err("Reloading memory is only possible in maintenance mode.");
        if(isset($_POST['compel']) && $_POST['compel']=='true')goto startCompelInit;

        $compelAll = false;
        goto noSpecify;
        startCompelInit:
        $compelAll = true;
        $isAchv = false;
        goto noSpecify;
        startAchvInit:
        $isAchv = true;
        noSpecify:

        echo $colortxt("Loading the ".($isAchv?"achievements data":"language-text file")." into memory......",
            colorProcess).PHP_EOL;
        $shmopMaxSize = $isAchv? $config->GetShmopAchvMaxsz(): $config->GetShmopLangMaxsz();
        $shmop = shmop_open($isAchv?$config->GetShmopIdAchv():$config->GetShmopIdLang(),
            'c', permissionFull, $shmopMaxSize);
        if($shmop===false) $err("Unable to create or open the SHMOP object.");

        $jsonPath = $_SERVER['DOCUMENT_ROOT'].($isAchv?"/local/achv/achv.json":"/lang/lang.json");
        $decoded = json_decode(file_get_contents($jsonPath), true);
        if($decoded===null)$err("Failed to parse JSON file: \"". $jsonPath."\"");

        $itemCount =!$isAchv?count($decoded):from($decoded)->sum(function($val){return count($val) + 1;});
        $spaceCount = $itemCount * $config->GetShmopHashtableMulti();
        $shmopHashSize = min(20, ceil(log($shmopMaxSize, 256) * 3));
        $shmopOffsetSize = ceil(log($shmopMaxSize, 256));
        $shmopTupleSize = $shmopHashSize + $shmopOffsetSize*2;
        $bytesTakenByHashtable =$shmopTupleSize * $spaceCount;
        $byte = 4 + $bytesTakenByHashtable;

        if($config->GetShmopHashtableMulti()<2)$err("config \"shmop_hashtable_multi\" should at least be 2.");

        shmop_write($shmop,hex2bin(str_repeat('00',$shmopMaxSize)),0);
        echo $colortxt("Washed the SHMOP memory to $shmopMaxSize bytes.",colorSubprocess);

        shmop_write($shmop,$unsigned2bin($byte,2),0);
        shmop_write($shmop,$unsigned2bin($shmopHashSize,1),2);
        shmop_write($shmop,$unsigned2bin($shmopOffsetSize,1),3);
        echo $colortxt("Hash size: $shmopHashSize, Offset size: $shmopOffsetSize, Hashtable ends at offset: $byte",colorSubprocess);

        $remap = array();
        $timesHit = 0;
        $timesOff = 0;

        $record = function($key, $value)use
        (&$timesHit,&$timesOff,&$byte,$bytesTakenByHashtable,$shmop,$shmopHashSize,$shmopTupleSize,
            $spaceCount,$shmopOffsetSize,$shmopMaxSize,&$remap,$unsigned2bin,$colortxt,$err){
            $size = strlen($value);
            if($byte+$size>=$shmopMaxSize)
                $err("Input data exceeding the max memory: $shmopMaxSize bytes.");

            shmop_write($shmop, $value, $byte);
            if(isset($remap[$key]))echo $colortxt("WARNING: Duplicate of key \"$key\"",colorError);
            $remap[$key] = [$byte, $size];

            $textcodeHash = substr(sha1($key),0,$shmopHashSize*2);
            $indexInTable = (hexdec($textcodeHash) % $spaceCount) * $shmopTupleSize;

            $timesHit++;
            while(shmop_read($shmop, $indexInTable+4, $shmopTupleSize)!==
                str_repeat(hex2bin('00'),$shmopTupleSize))
            {
                $timesHit++;
                $timesOff++;
                $indexInTable+=$shmopTupleSize;
                if($indexInTable>=$bytesTakenByHashtable)$indexInTable-=$bytesTakenByHashtable;
            }
            shmop_write($shmop, hex2bin($textcodeHash).$unsigned2bin($byte,$shmopOffsetSize).
                $unsigned2bin($size,$shmopOffsetSize),4+$indexInTable);

            $byte += $size;
        };

        /** @noinspection PhpIfWithCommonPartsInspection */
        if($isAchv) foreach ($decoded as $chapName => $chapter){
            $toRec = array();
            $chapSize = from($chapter)->where(function($v){return gettype($v)==='array';})
                ->orderBy(function($v){return (!isset($v['visible']) || $v['visible']=='true')?0:1;})
                ->sum(function ($v,$k)use(&$toRec,$shmopOffsetSize){
                    $achv = json_encode($v, JSON_FORCE_OBJECT);
                    $size = strlen($achv)+$shmopOffsetSize;
                    $achv = hex2bin(str_pad(dechex($size),$shmopOffsetSize*2,'0',STR_PAD_LEFT)).$achv;
                    $toRec[]= [$k,$achv];
                    return $size;}
                ) + $shmopOffsetSize;
            $chapAttr =json_encode(from($chapter)->where(function($v){return gettype($v)!=='array';})->toArray(),JSON_FORCE_OBJECT);
            $record($chapName, hex2bin(str_repeat('00',$shmopOffsetSize).
                    str_pad(dechex($chapSize),$shmopOffsetSize*2,'0', STR_PAD_LEFT).
                    str_pad(dechex(strlen($chapAttr)),$shmopOffsetSize*2,'0', STR_PAD_LEFT)).$chapAttr);
            foreach ($toRec as $rec)$record($rec[0],$rec[1]);
        }
        else foreach ($decoded as $textcode => $textItem){
            $content = from($textItem)->
            aggregate(function ($a,$text,$lang){return "$a<$lang>$text</$lang>";},'');
            $record($textcode, $content);
        }

        $hitAccuracy =1-$timesOff/$timesHit;
        echo $colortxt("Memory loaded to offset $byte for a maximum at $shmopMaxSize bytes. Space used ".
                round(100.0*$byte/$shmopMaxSize,2)."%", colorSubprocess).PHP_EOL;
        echo $colortxt("Hashtable hits: ".$timesOff." misses in ".$timesHit." hits. Accuracy: ".
                round($hitAccuracy*100,2)."%",colorSubprocess).PHP_EOL;
        if($hitAccuracy<=0.5)
            echo $colortxt("WARNING: Hashtable hit accuracy is too low. May hinder performance at reading memory.", colorError).PHP_EOL;

        echo $colortxt("Remapping the function calls in the PHP files......",colorProcess).PHP_EOL;
        $remapCode = $isAchv? 'ACHV':"REMAP";
        $pattern = /** @lang RegExp */
            "/(\d+, *\d+|\d+|)( *\/\* *".$remapCode."%([a-z\d][a-z\d_]+[a-z\d?!]) *\*\/)/";
        echo $colortxt("Using REGEX pattern: ".$pattern,colorSubprocess).PHP_EOL;

        $filecnt = 0;
        $totalrepcnt = 0;
        $remapInFiles = function(string $dir) use (&$remapInFiles, $colortxt, $remap, $remapCode, $pattern,
            &$filecnt, &$totalrepcnt):void{
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
                        echo $colortxt("WARNING: Failed to load file for read and write: \"$path\"", colorError).PHP_EOL;
                        continue;
                    }
                    $linecnt = 0;
                    $dealcnt = 0;
                    for ($i=0;$i<=count($lines)-1;$i++){
                        $replaced = preg_replace_callback($pattern,
                            function($matches)use($colortxt, $path, $remap, $linecnt, &$dealcnt)
                            {
                                $textcode = $matches[3];
                                if(!isset($remap[$textcode])){
                                    echo $colortxt("WARNING: Undefined textcode \"$textcode\" demanded in file: \"$path\" at line ".$linecnt,colorError).PHP_EOL;
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
                    $totalrepcnt += $dealcnt;
                    $filecnt++;
                    echo $colortxt("Made ".$dealcnt." replacements in file: \"".$path."\"",colorSubprocess). PHP_EOL;
                }
            }
        };
        $remapInFiles($_SERVER['DOCUMENT_ROOT']);
        echo $colortxt("Summary: Made $totalrepcnt replacements in $filecnt files.",colorSubprocess). PHP_EOL;
        echo $colortxt("Success!",colorSuccess).PHP_EOL;
        if($compelAll&&!$isAchv){
            echo $colortxt("Turning to load the achievements data......",colorSubprocess).PHP_EOL;
            goto startAchvInit;
        }
        elseif (!$compelAll&&!$isAchv){
            echo $colortxt("You may want to load & reload the ACHV memory:",colorSubprocess).PHP_EOL;
             $postButton('init_achv', 'Load or Reload Achievements memory');
        }
        echo clickHereToContinue;
    }

    //- INITIALIZE OR RELOAD MAINTENANCE DATA -//
    elseif (isset($_POST['init_maint']) && $_POST['init_maint']=='true'){
        if($maint_initialized && !$config->InMaintenance() &&!$config->IsDebug())$err("No maintenance is currently in progress.");
        $quitMaint = $maint_initialized && !$config->NoMaintenance();
        echo $colortxt("Loading initial maintenance status memory......",colorProcess);
        $shmop_maint= shmop_open($config->GetShmopIdMaintenance(),'c',permissionFull,26);
        $initmem = str_repeat('0',26);
        shmop_write($shmop_maint, $initmem, 0);
        echo $colortxt("Written to the maintenance memory: \"$initmem\"",colorSubprocess).PHP_EOL;
        if($quitMaint){
            echo $colortxt("Successfully reset maintenance memory. Requiring a full memory reload.",
                colorSubprocess).PHP_EOL;
            goto startCompelInit;
        }
        else echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    //- START A MAINTENANCE with TEXT AND TIME -//
    elseif(isset($_POST['maint_hrs'], $_POST['maint_mins'], $_POST['maint_text'])){
        if(!$maint_initialized || !$lang_initialized || !$db_initialized)
            echo $err("Make sure everything is initialized well.");
        if(!$config->NoMaintenance())
            echo $err("Already under maintenance or already issued a maintenance.");

        $maintHrs = $_POST['maint_hrs'];
        $maintMins = $_POST['maint_mins'];
        if (filter_var($maintHrs, FILTER_VALIDATE_INT)===false || $maintHrs>24 || $maintHrs<0 ||
            filter_var($maintMins, FILTER_VALIDATE_INT)===false || $maintMins>59 || $maintMins<0 ||
            ($maintHrs==0&&$maintMins==0))$err("The maintain time number input is invalid.");
        $mustHaveGoodMaintText('maint_text');
        echo $colortxt("Issuing a maintenance after $maintHrs hours $maintMins minutes......",
                colorProcess).PHP_EOL;
        $now = new DateTime();
        $add = new DateInterval('PT'.$maintHrs.'H'.$maintMins.'M');
        $toWrite = $now->add($add)->format('Y-m-d H:i:s.u');
        shmop_write($shmop_maint, $toWrite, 0);
        echo $colortxt("Written to the maintenance memory: \"$toWrite\"",colorSubprocess).PHP_EOL;
        $_POST['change_maint_text'] = $_POST['maint_text'];
        goto startChangeMaintText;
    }

    //- CHANGE THE MAINTENANCE TEXT -//
    elseif(isset($_POST['change_maint_text'])){
        $mustInitMaint();
        if($config->NoMaintenance())$err("Currently no maintenance is issued.");
        $mustHaveGoodMaintText('change_maint_text');
        startChangeMaintText:
        $targetText =$simplifyMaintText($_POST['change_maint_text']);
        echo $colortxt("Writing the maintenance text to \"_maint.txt\"......",colorProcess);
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/_maint.txt", $targetText);
        echo $colortxt("The maintenance text is written as below:<br>".
                str_replace(["\r\n","\n"],"<br>",htmlspecialchars($targetText)),colorSubprocess).PHP_EOL;
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
        else $err("Things are going wrong. Need debug."); //todo database
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
        echo $colortxt("Written to the maintenance memory: \"$toWrite\"",colorSubprocess).PHP_EOL;
        echo $colortxt("Success!",colorSuccess).clickHereToContinue;
    }

    // DECRYPT THE LOGGING MESSAGE
    elseif(isset($_POST['log_dec'])){
        echo $colortxt("Decrypting the input data......",colorProcess).PHP_EOL;
        $bin= hex2bin(trim($_POST['log_dec']));
        if($bin===false)$err("It's not a valid hex input.");
        $msg = decrypt($bin,$config->GetLogEncryptKey());
        if($msg===null)$err("Failed to decrypt.");
        $msg=str_replace(["\r\n","\n"],"<br>",$msg);
        echo $colortxt("Success! The decrypted message is below:",colorSuccess).PHP_EOL.
            $colortxt($msg, colorSubprocess).PHP_EOL;
        echo clickHereToContinue;
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

if(!$maint_initialized){
    echo $colortxt("The maintenance data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_maint', 'Click here to load it');
}
else{
    echo $colortxt("Maintenance data loaded fine.",colorSuccess).PHP_EOL;
    $maintainTime = $config->GetMaintenanceTime();

    $getDefMaintText=function()use($config):string{
        $defMaintText = '';
        foreach ($config->GetAllLanguages() as $lang)
            $defMaintText .= "<$lang>".PHP_EOL.($config->IsDebug()?"TEST VALUE":"").PHP_EOL. "</$lang>".PHP_EOL;
        return $defMaintText;
    };

    if($maintainTime===false){
        echo "<form action='server.php' method='post'>".
            "Issue a maintanence for <input type='number' name='maint_hrs' value='1' max='24' min='0'> hours ".
            "<input type='number' name='maint_mins' value='0' max='59' min='0'> minutes <br>with the message:<br>".
            "<textarea name='maint_text' style='height: 300px; width: 500px; resize: none;'>{$getDefMaintText()}".
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
            "<textarea name='change_maint_text' style='height: 300px; width: 500px; resize: none;'>".
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
    echo $colortxt("The language-text file hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_lang', 'Click here to load language-text memory');
}
else{
    echo $colortxt("Language-text memory loaded fine.",colorSuccess).PHP_EOL;
    if($config->InMaintenance()||$config->IsDebug()) {
        echo "<form action='server.php' method='post'><input type='text' style='display: none;' name='init_lang' ".
            "value='true'><input type='submit' value='Reload language-text memory'".($config->IsDebug()?' (DEBUG)':'').
            "><br><input type='checkbox' name='compel' value='true' checked>Meanwhile, reload the achievements memory?</form>" .PHP_EOL;
    }
}
if(!$achv_initialized&&$lang_initialized){
    echo $colortxt("The achievements data hasn't been loaded into memory.",colorError).PHP_EOL;
    $postButton('init_achv', 'Click here to load achievements memory');
}
elseif(!$achv_initialized){
    echo $colortxt("The ACHV data hasn't been loaded - load the language-text memory first!",colorError).PHP_EOL;
}
else{
    echo $colortxt("Achievements memory loaded fine.",colorSuccess).PHP_EOL;
    if(($config->IsDebug())) $postButton('init_achv', 'Reload achievements memory (DEBUG ONLY)');
}

if($db_initialized){
    echo $colortxt("DatabaseSystem is loaded successfully.",colorSuccess).PHP_EOL;
    if(!$config->InMaintenance() && !$config->IsDebug())
        echo $colortxt("WARNING: ONLY MODIFY THE DATABASE AT MAINTENANCE MODE!!!").PHP_EOL;
    else echo $colortxt($config->IsDebug()?
            "Debugging the database you'd refer to Intellij IDEA...... You know that all!":
            "Changes about database you should better refer to phpMyAdmin, etc......").PHP_EOL;
}
else{
    echo $colortxt("DatabaseSystem is not properly set up......",colorError).PHP_EOL;
    $postButton('db_init', "Initialize the DatabaseSystem!");
}

echo "<form action='server.php' method='post'>Decrypt the logs message:<br>".
    "<textarea name='log_dec' style='height: 200px; width: 500px; resize: none;'></textarea><br>".
    "<input type='submit' value='Decrypt This! I wanna see the hell inside...!'></form>";

echo PHP_EOL;
http_response_code(200);

