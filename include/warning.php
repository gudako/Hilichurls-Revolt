<?php
require_once 'initialize.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
use Game\Config;
$config = new Config();
$isServerDown = $config->IsServerDown();
$inMaintenance = $config->InMaintenance();
$alright = !$isServerDown && !$inMaintenance;
?>
<?php echo $alright?'<noscript id="noscript_checker">':'';?>
    <link href="../css/window.css" rel="stylesheet"/>
    <div id="modal_back">
        <div id="window" style="font-size: 21px; padding: 16px 6px; text-align: center;
         border: 3px #d70303 solid;">
            <img src="../img/pages/icons/<?php if($isServerDown)echo 'server_down';elseif($inMaintenance)echo 'maintenance';else echo 'warning';?>.png"
                 style="width: 42px; height: auto; display: block; margin: 0 auto 8px auto">
            <div>
                <?php
                if($isServerDown) echo "<font style='font-weight: bold;'>SERVER ERROR<br>The server is currently down.</font>";
                elseif($inMaintenance){
                    echo "<font style='font-weight: bold;'>".text('maintenance_alert_title').'</font>';
                    echo "<br><div style='border: 1px gray solid; padding: 6px; margin: 10px 20px 5px 20px;".
                        " font-size: 14px; text-align: left; max-height: 200px; overflow-y: scroll; color: #3d3b3b;'>";
                    $mText = file_get_contents($_SERVER['DOCUMENT_ROOT']."/_maintenance.txt");
                    $lang = get_lang();
                    $matches = array();
                    preg_match("/(?<=<".$lang.">).*(?=<\/".$lang.">)/",$mText,$matches);
                    echo $matches[0].'</div>';
                }
                else echo text('pages_js_alert_text');
                ?>
            </div>
        </div>
    </div>
<?php echo $alright?'</noscript>':'';?>
