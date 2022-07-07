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
<?php include 'mainbar.php'; include 'lowbar.php';?>
<link href="css/mainpage.css" rel="stylesheet"/>
<div id="bar1">
    <div id="bar1_clip_img"> <img src="img/pages/mainpage1.jpg"></div>
    <div id="bar1_advocate"><div>Help us FIGHT BACK the traveller</div></div>
</div>
<div id="bar2">
    We are the hilichurls, in Teyvat.
</div>
<div id="bar3">

</div>
</body>
