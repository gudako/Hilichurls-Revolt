<?php if(!isset($_SESSION))session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lang/lang.php'?>

<div id="tip" style="position: fixed; left: 0; right: 0; opacity: 0.89; color: #c04444; z-index: 33; top: 82px; text-align: center; display: none">
    <span style="margin: auto; border-radius: 4px; border: 3px #c04444 solid; font-size: 16px; padding: 6px; background-color: rgba(245, 245, 245, 0.9);">
        <?php echo text('maintenance_issued_alert');?>
    </span>
</div>
<script>
    let originText = null;
    const grab = function ()
    {
        $.get("../script/calls/maint_check.php").done((data)=>
        {
            const ret = data.toString();
            if(ret==='true')location.reload();
            else if(ret!=='false')
            {
                const hrs = ret.split(',')[0];
                const min = ret.split(',')[1];
                const spanObj = $('#tip>span');
                spanObj.text(originText.replace('%H',hrs).replace('%I',min));
                $('#tip').css('display','block');
            }
        });
    }

    $(document).ready(()=>{
        originText = $('#tip>span').text();
        grab();
        setInterval(grab, 10000);
    });
</script>

