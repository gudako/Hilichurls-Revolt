<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";?>
<p>
    <?php echo memtxt(2256,76/*REMAP%setlang_window_content*/);?>
</p>
<select id="windowed_lang" style="margin: 0 0 15px 0;">
    <option value="en" <?php echo getlang()=="zh"?"selected":"";?>>English</option>
    <option value="zh" <?php echo getlang()=="en"?"selected":"";?>>中文</option>
</select>
<p id="submit_line" style="margin: 10px 0; display: flex;">
    <a class="window_press_button" style="margin-right: 16px"
       onclick="setLangCookie('windowed_lang')">
        <?php echo memtxt(2332,31/*REMAP%common_confirm*/);?>
    </a>
    <a class="window_press_button" onclick="modelWindow.close();"><?php echo memtxt(2363,30/*REMAP%common_cancel*/);?></a>
</p>