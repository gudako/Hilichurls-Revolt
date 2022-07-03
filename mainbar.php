<?php
require 'initialize.php';
include_once 'lang_file/lang.php';
?>
<link href="css/mainbar.css" rel="stylesheet"/>
<div id="mainbar">
    <img id="logo" src="img/pages/logo_<?php echo get_lang();?>.png">

    <!--only shown on larger screen-->
    <?php
    function add_normal_link(string $name, string $textcode){
        echo '<span class="norm"><img src="img/pages/icons/'. $name .'.png">'. '<a href="'. $name .'.php">'.
            text($textcode) . '</a></span>' . PHP_EOL;
    }
    add_normal_link('mainpage','mainbar_mainpage');
    add_normal_link('achievements','mainbar_achievements');
    add_normal_link('bosses','mainbar_bosses');
    ?>

    <!--only shown on smaller screen-->
    <span id="top_menu_button">
        <img id="top_list_icon" src="img/pages/icons/top_list.png">
    </span>

    <!--the login button-->
    <span id="login_button">
        <img src="img/pages/icons/play.png">
        <?php echo text('mainbar_play');?>
    </span>

    <!--dropdown list to set language-->
    <script>
        function setLang(){
            const date = new Date();
            date.setTime(date.getTime() + (365*24*60*60*1000));
            const expires = "; expires=" + date.toUTCString();
            const sel = document.getElementById("lang");
            const value = sel.options[sel.selectedIndex].value;
            document.cookie = "lang=" + (value || "")  + expires + "; path=/";
            location.reload();
        }
    </script>
    <select id="lang" onchange="setLang()">
        <option value="en" <?php echo get_lang()=='en'?'selected':'';?>>English</option>
        <option value="zh" <?php echo get_lang()=='zh'?'selected':'';?>>Chinese</option>
    </select>

    <!--dropdown list for smaller screen-->
    <div id="top_menu">
        <a></a>
    </div>

</div>