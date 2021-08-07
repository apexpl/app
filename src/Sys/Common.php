<?php
declare(strict_types = 1);

use Apex\Svc\Di;
use Symfony\Component\String\UnicodeString;


function handleException($e)
{
    echo "TYPE: " . $e::class . "\n";
    echo "ERROR: " . $e->getMessage() . "\nFile: " . $e->getFile() . "::" . $e->getLine(); exit;
}

function handleError(int $code, string $msg, string $file, int $line)
{

        if (str_contains($msg, 'follows optional parameter')) { return; }

    header("Content-type: text/plain");
    echo "Handle ERROR: $msg\n\nFile: $file\nLine: $line\n"; 
    //print_r(debug_backtrace());
    exit;

}

/**
 * Format case
 */
function fcase(string $word, string $case = 'title'):string
{

    // Init
    $string = new UnicodeString($word);

    // Get new case
    $word = match ($case) { 
        'upper' => strtoupper($word), 
        'lower' => strtolower($word), 
        'camel' => $string->camel(), 
        'title' => $string->camel()->title(), 
        default => $word
    };

    // Return
    return (string) $word;
}


/**
 * Translate
 */
function tr(string $data):string
{
    return $data;
}


