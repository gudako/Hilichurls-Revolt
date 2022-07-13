<?php require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php"; ?>
<head>
    <title><?php echo memtxt(1057,53/*REMAP%game_name*/).' - '.
            memtxt(4450,36/*REMAP%achievements_title_suffix*/);?></title>
</head>
<body>
<link href="css/commonplace.css" rel="stylesheet"/>
<link href="css/achievements.css" rel="stylesheet"/>
<div class="clip">
    <?php require_once 'include/page.php';?>
    <div id="getRoof" class="_top">
        <div id="noLogin" style="display: none">
            The table below only includes unhidden achievements.<br>
            <a>Login</a> to see all your achievements.
        </div>
        <div id="hasLogin">
            <div id="achvText">Your current ACHV progress:</div>
            <div class="backBar">
                <div class="bar"></div>
                <div class="barText">24.5%</div>
            </div>
            <div class="achvTextBox">
                <img src="img/game/achi-point.png">
                <div>100<sub><sub>/3000</sub></sub></div>
            </div>
        </div>
    </div>
    <div id="achvTable">
        <div id="achvTabLeft"></div>
        <div id="achvTabRt"></div>
        <div id="achvTabRight"></div>
    </div>

</div>
<div id="achvScript">
<?php

?>
</div>
</body>
