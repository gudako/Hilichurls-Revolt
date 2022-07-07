<?php require 'initialize.php';
    if(isset($_GET['lang'])) {
        setcookie('lang', $_GET['lang'], time()+(60*60*24*365));
        header('Location: index.php');
        die();
    }
?>
<head>
    <title><?php echo text('game_name');?></title>
</head>
<body style="margin: 0; padding: 0">
<?php include 'mainbar.php'; include 'lowbar.php'; include 'vert_screen_alert.php';?>
<link href="css/mainpage.css" rel="stylesheet"/>
<div id="bar1">
    <div id="bar1_clip_img" class="clip_img"> <img src="img/pages/mainpage1.jpg"></div>
    <div id="bar1_advocate"><div><?php echo text('mainpage_bar1_text');?></div></div>
</div>
<div id="bar2">
    <?php echo text('mainpage_bar2_text');?>
</div>
<div id="bar3">
    <div id="bar3_clip_img" class="clip_img"><img src="img/pages/mainpage2.jpg"></div>
    <div id="bar3_sub_img" class="clip_img"><img src="img/pages/mainpage3.png"></div>
    <div id="bar3_advocate"><?php echo text('mainpage_bar3_text2');?></div>
    <div id="bar3_statement"><?php echo text('mainpage_bar3_text1');?></div>
</div>
<div id="bar4">
    <div>
        <?php echo text('mainpage_bar4_text');?>
    </div>
</div>
</body>
