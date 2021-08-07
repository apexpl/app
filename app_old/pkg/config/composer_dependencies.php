<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\app\exceptions\ApexException;


/**
 * Handles Composer dependencies package configuration.
 */
#[used_by(config::class)]
class composer_dependencies extends config
{

/**
 * Install composer dependencies
 */
public static function install():void
{

    // Initialize
    $dependencies = $this->pkg->composer_dependencies ?? [];
    if (count($dependencies) == 0) { 
        return;
    }

    // Get composer.json file
    $vars = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true);

    // Go through dependencies
    foreach ($dependencies as $key => $value) { 
        $vars['require'][$key] = $value;
    }

    // Save composer.json file
    file_put_contents(SITE_PATH . '/composer.json', json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

}

}

