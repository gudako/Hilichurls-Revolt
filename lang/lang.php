<?php
require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";

/**
 * Get the current language.
 * @return string The current language.
 */
function getlang():string{
    if(!isset($_COOKIE["lang"])) return "en";
    $lang_cookie = $_COOKIE["lang"];
    $config=new \local\ConfigSystem();
    $lang_set = $config->GetAllLanguages();
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : "en";
}

/**
 * Get the exact readable text in the current language with a textcode, or directly from a memory part.
 * Pass in any arguments that matches one of below:
 * (1). A string representing the textcode;
 * (2). A int with the memory offset, another with the size;
 * (3). The two ints in (2) packed into an array.
 * @return string The readable text in the current language.
 */
function memtxt():string{
    $comment = "Function \"memtxt\" can be called with argument: (int \$offset, int \$size) OR (string \$textcode) OR ".
        "(array \$offsetAndSize). When called with array, the array structure must be \$offsetAndSize = [int \$offset, int \$size].";

    $config = new \local\ConfigSystem();
    $shmop = shmop_open($config->GetShmopIdLang(), 'a', 0600, $config->GetShmopSizeLang());

    $args= func_get_args();
    if(count($args) == 1){
        if(gettype($args[0])==="array"){
            if(count($args[0])!==2||gettype($args[0][0])!=="integer"||gettype($args[0][1])!=="integer")
                throw new TypeError("Function called with invalid array, must be [int \$offset, int \$size]",EX_CODE_EXPECTED);
            return memtxt($args[0][0], $args[0][1]);
        }
        if(gettype($args[0])!=="string")
            throw new TypeError("Function called with unrecognized type",EX_CODE_EXPECTED);
        return memtxt(\utils\TextcodeParser::ParseByTextcode($shmop, $args[0]));
    }
    elseif(count($args) == 2){
        $offset = $args[0];
        $size = $args[1];
        if(gettype($offset)!=='integer'||gettype($size)!=='integer')
            throw new TypeError("\$offset and \$size they must be integers",EX_CODE_EXPECTED);

        $langItem = shmop_read($shmop, $offset, $size);
        $lang = getlang();
        $matches = array();
        if(preg_match("/(?<=<$lang>)(.|\r|\n)*(?=<\/$lang>)/m",$langItem,$matches)!==1 || !isset($matches[0]))
            throw new Error("The specified textcode does not have an definition for the language named \"$lang\".",EX_CODE_IMPORTANT);
        return $matches[0];
    }
    else throw new ArgumentCountError($comment,EX_CODE_EXPECTED);
}
