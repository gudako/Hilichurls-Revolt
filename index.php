<?php
    require 'initialize.php';
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
    <?php include 'mainbar.php'?>
</body>
