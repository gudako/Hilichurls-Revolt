<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";?>
<p>
    <?php echo memtxt(2191,76/*REMAP%setlang_window_content*/);?>
</p>
<select id="windowed_lang" style="margin: 0 0 15px 0;">
    <option value="en" <?php echo getlang()=='zh'?'selected':'';?>>English</option>
    <option value="zh" <?php echo getlang()=='en'?'selected':'';?>>中文</option>
</select>
<p id="submit_line" style="margin: 10px 0; display: flex;">
    <a class="press_button" style="margin-right: 16px"
       onclick="setLang('windowed_lang')">
        <?php echo memtxt(2267,31/*REMAP%common_confirm*/);?>
    </a>
    <a class="press_button" onclick="closeWindow()"><?php echo memtxt(2298,30/*REMAP%common_cancel*/);?></a>
</p>