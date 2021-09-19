<?php
declare(strict_types = 1);

use Apex\Svc\{Di, App};

/**
 * translate message with placeholders
 */
function tr(...$args):string
{ 

    // Initialize
    $text = array_shift($args);
    if (isset($args[0]) && is_array($args[0])) {
        $args = $args[0]; 
    }

    // Translate text, if available
    $app = Di::get(App::class);
    //$lang = $app->getLanguage();
    //if ($lang != 'en' && $row = db::get_row("SELECT * FROM internal_translations WHERE language = %s AND md5hash = %s", $lang, md5($text))) { 
        //if ($row['contents'] != '') { $text = base64_decode($row['contents']); }
    //}

    // Go through args
    $x=1;
    $replace = [];
    foreach ($args as $key => $value) {
        if (is_array($value)) { continue; }

        $pos = strpos($text, "%s");
        if ($pos !== false) {
            $text = substr_replace($text, (string) $value, $pos, 2);
        }

        if (is_string($key)) { $replace['{' . $key . '}'] = $value; }
        $replace['{' . $x . '}'] = filter_var($value, FILTER_SANITIZE_STRING);
    $x++; }

    // Return
    return strtr($text, $replace);

}




