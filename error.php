<?php
if($_SERVER['REQUEST_METHOD']!=='GET'){
    http_response_code(400);
    die();
}
$lang = $_GET['lang'] ?? 'en';
$logcode = $_GET['logcode'] ?? null;
$msg = $_GET['msg'] ?? null;
if(!($logcode===null xor $msg===null)){
    http_response_code(400);
    die();
}
else http_response_code(500);
?>
<head>
    <title>500 Internal Server Error</title>
</head>
<style>
    body{
        margin: 0;
        padding: 0;
        font-family: 'Noto Sans SC', sans-serif;
    }
    #topic{
        background-color: darkred;
        padding: 0;
        margin: 0;
        height: min(calc(66.6667px + 21.6667vw),240px);
    }
    #topic>div#stuck{
        font-weight: 700;
        color: antiquewhite;
        font-size: min(calc(26.6667px + 3.66667vw),56.00006px);
        padding-left: min(calc(-20px + 8.5vw),48px);
        padding-top: min(7.2vw,57.6px);
    }
    #topic>div{
        color: wheat;
        font-size: min(calc(9.33333px + 1.13333vw),18.39997px);
        padding-left: min(calc(-113.333px + 33.6667vw),156.0006px);
        padding-top: min(calc(13.3333px + 4.33333vw),47.99994px);
    }
    #topic>img{
        display: block;
        height: auto;
        position: absolute;
        width: min(calc(-40px + 38vw),264px);
        top: max(calc(106.667px - 11.3333vw),16.0006px);
        left: calc(-270.4px + 97.8vw);
    }
    .texts{
        margin:11px 30px 0 30px;
        font-size: min(calc(13.3333px + 0.533333vw),17.599964px);
    }
    a{
        display: block;
        font-size: 14px;
        margin-top: 12px;
        margin-left: 4px;
    }
    @media only screen and (max-width: 800px) {
        #topic>img{
            left: calc(80px + 54vw);
        }
    }
</style>
<body>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC&display=swap" rel="stylesheet">
<div id="topic">
    <div id="stuck">
        <?php
        if($lang === 'en')
            echo "WE ARE STUCK!";
        elseif ($lang ==='zh')
            echo "服务器错误......";
        ?>
    </div>
    <div>
        <?php
        if($lang === 'en')
            echo "That saying, we got those bugs in file......";
        elseif ($lang ==='zh')
            echo "指的是，这些文件里有一些奇怪的BUG......";
        ?>
    </div>
    <img src="img/pages/bug.png">
</div>
<a href="">chang lang</a>
<div class="texts" style="margin-top:40px;">
    <?php
    if($lang === 'en')
        echo "It's not your problem. It's our fault. There are something buggy in the server.";
    elseif ($lang ==='zh')
        echo "这不是你的问题——是我们的问题。服务器里可能出了一些BUG......";
    ?>
</div>
<div class="texts" style="<?php if($logcode===null)echo 'display:none';?>">
    <?php
    if($lang === 'en')
        echo "The error is successfully logged to the database, and we will deal with it as soon as possible.";
    elseif ($lang ==='zh')
        echo "错误信息已经被成功发送到了数据库中，我们将尽快修复该问题。";
    ?>
</div>
<div class="texts" style="<?php if($msg===null)echo 'display:none';?>">
    <?php
    if($lang === 'en')
        echo "Meanwhile, we are not able to log the error data to the database :(";
    elseif ($lang ==='zh')
        echo "同时，我们未能成功将此次错误信息发送至数据库 :(";
    ?>
</div>
<div class="texts">
    <?php
    if($lang === 'en')
        echo "If the problem persists, you can get in contact with us send the following code to email \"lostbelt@protonmail.com\", we'll reply you fast.";
    elseif ($lang ==='zh')
        echo "如果该问题持续存在，你可以把下面的代码发送到邮箱 \"lostbelt@protonmail.com\" 中，来和我们获得联系。我们会在第一时间内处理此问题。";
    ?>
</div>
<div class="texts" style="font-size: 34px;<?php if($logcode===null)echo 'display:none';?>">
    <?php echo 'ERR'.$logcode;?></div>
<div class="texts" style="border:1px #3b0909 solid;margin:20px;padding:10px;color:darkslategrey;
<?php if($msg===null)echo 'display:none';?>"><?php echo $msg;?></div>
<div class="texts">
    <?php if($lang === 'en') echo "Sincere apology to all the inconveniences we may have caused to you.";
    elseif ($lang ==='zh') echo "同时，我们诚挚地为所有可能对您造成的不便表示抱歉。";?>
</div>
<?php


?>
</body>
