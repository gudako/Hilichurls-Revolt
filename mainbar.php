<?php
require 'initialize.php';
include_once 'lang_file/lang.php';
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<link href="css/mainbar.css" rel="stylesheet"/>
<div id="mainbar">
    <img id="logo" src="img/pages/logo_<?php echo get_lang();?>.png">

    <!--only shown on larger screen-->
    <?php
    function add_normal_link(string $name, string $textcode){
        echo '<a class="norm" href="'. $name .'.php"><img src="img/pages/icons/'. $name .'.png">'. '<span>'.
            text($textcode) . '</span></a>' . PHP_EOL;
    }
    function add_normal_links(){
        add_normal_link('mainpage','mainbar_mainpage');
        add_normal_link('achievements','mainbar_achievements');
        add_normal_link('bosses','mainbar_bosses');
    }
    add_normal_links();
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
            document.cookie = "lang=" + ($("#lang option:selected").val() || "")  + expires + "; path=/";
            location.reload();
        }
    </script>
    <select id="lang" onchange="setLang()">
        <option value="en" <?php echo get_lang()=='en'?'selected':'';?>>English</option>
        <option value="zh" <?php echo get_lang()=='zh'?'selected':'';?>>Chinese</option>
    </select>

    <!--dropdown list for smaller screen-->
    <div id="top_menu" style="display: none">
        <?php add_normal_links();?>
        <a class="norm"><span><?php echo text('mainbar_menu_change_lang');?></span></a>
    </div>

    <script>
        const topMenu = $('#top_menu');
        const topMenuButton = $('#top_menu_button');

        const fadeOutTopMenu = function(){
            $(document).add($(window)).off('.closeMenu');
            topMenu.fadeOut('fast', ()=>topMenuButton.removeAttr('opened'));
        }

        topMenuButton.click(function(){
            if(topMenuButton.attr('opened') !== 'true'){
                let pos = topMenuButton.offset();
                pos.top += 40;
                topMenu.fadeIn('fast', ()=> topMenuButton.attr('opened', true));
                topMenu.offset(pos);

                $(document).on('click.closeMenu', whenSthMoved);
                $(window).on('resize.closeMenu', whenSthMoved);
                $(window).on('scroll.closeMenu', whenSthMoved);
            }
            else fadeOutTopMenu();
        })

        const whenSthMoved = function(event) {
            if(event.type === 'click'){
                const target = $(event.target);
                if(!target.closest(topMenu).length && !target.closest(topMenuButton).length) fadeOutTopMenu();
            }
            else fadeOutTopMenu();
        };
    </script>

</div>