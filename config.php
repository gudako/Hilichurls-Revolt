<?php
if(!isset($GLOBALS['debug'])) $GLOBALS['debug'] =
    json_decode(file_get_contents(__DIR__.'/config.json'),true)['mode_debug']=='true';

function isDebug(): bool
{
    return $GLOBALS['debug'] === true;
}
