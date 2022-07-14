<?php http_response_code(500); ?>
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
    #texts{
        margin:40px 30px 0 30px;
        font-size: min(calc(13.3333px + 0.533333vw),17.599964px);
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
    <div id="stuck">WE ARE STUCK!</div>
    <div>That saying, we got those bugs in file......</div>
    <img src="img/pages/bug.png">
</div>
<div id="texts">
    It's not your problem. It's our fault. There are something wrong in the server.<br>
    The error is logged, and we will deal with it as soon as possible.
</div>
<?php


?>
</body>
