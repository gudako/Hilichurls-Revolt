<?php require 'initialize.php';?>
<p style="margin-right: 5px;">
    <?php echo text('setlang_window_content');?>
</p>
<select id="windowed_lang" style="margin: 0 0 15px 0;">
    <option value="en" <?php echo get_lang()=='en'?'selected':'';?>>English</option>
    <option value="zh" <?php echo get_lang()=='zh'?'selected':'';?>>中文</option>
</select>
<p id="submit_line" style="margin: 10px 0; display: flex;">
    <a class="press_button" style="margin-right: 16px" onclick="{setLang('windowed_lang');closeWindow();}">
        <?php echo text('common_confirm');?>
    </a>
    <a class="press_button" onclick="closeWindow()"><?php echo text('common_cancel');?></a>
</p>