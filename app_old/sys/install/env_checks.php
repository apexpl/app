<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use apex\app\cli\cli;


/**
 * Performs all necessary checks before installation to ensure 
 * environment meets all necessary requirements.
 */
class env_checks
{

    // Properties
    private static string $php_min_version = '8.0.0';
    private static array $errors = [];

    // Required PHP extensions
    private static array $php_extensions = [
        'mysqli', 
        'openssl', 
        'curl', 
        'json', 
        'mbstring', 
        'redis', 
        'tokenizer', 
        'gd', 
        'zip' 
    );

    // Files / directories that must be writeable.
    private static array $writeable = [
        '.env',    
        'storage/',
        'storage/logs' 
    ];


/**
 * Perform envinonment checks.
 */
public static function check():void
{

    // Ensure we're on CLI
    if (php_sapi_name() != "cli") { 
        die("This system is not yet installed.  Please run via CLI to initiate the installer.");
    }

    // Get command line arguments
    cli::get_args([
        'redis-host', 
        'redis-user', 
        'redis-password', 
        'redis-dbindex', 
        'dbname', 
        'dbuser', 
        'dbpassword', 
        'dbhost', 
        'dbport'
    ]);

    // Check PHP
    self::check_php();

    // Check filesystem
    self::check_filesystem();

    // Display errors, if needed
    if (count(self::$errors) > 0) { 
        echo "One or more errors were found.  Please resolve the below issues before continuing:\n\n";
        foreach (self::$errors as $msg) { 
            echo "    - $msg\n";
        }
        exit(0);
    }

}

/**
 * Check PHP
 */
private static function check_php():void
{

    // Check PHP version
    if (version_compare(phpversion(), $this->php_min_version, '<') === true) { 
        self::$errors[] = "Apex requires PHP v" . $this->php_min_version . ".  Please upgrade your PHP installation before continuing.";
        return;
    }

    // Check for Composer
    if (!file_exists(SITE_PATH . '/vendor/autoload.php')) { 
        self::$errors[] = "Composer dependencies not installed.  Before continuing, please first run: composer --no-dev update";
    }

    // Check PHP extensions
    foreach (self::$php_extensions as $ext) { 
        if (!extension_loaded($ext)) {
            self::$errors[] = "The PHP extension '$ext' is not installed, and is required.";
        }
    }

}

/**
 * Check filesystem
 */
private static function check_filesystem():void
{

    // Define required directories
    $req_dirs = [
        'storage/logs', 
        'views/components', 
        'views/components/htmlfunc', 
        'views/components/modal', 
        'views/components/tabpage'
    ];

    // Create required directories
    foreach ($req_dirs as $dir) { 
        if (is_dir(SITE_PATH . '/' . $dir)) { 
            continue; 
        }
        @mkdir(SITE_PATH . '/' . $dir, 0777, true);
    }

    // Check writable files / dirs
    foreach (self::$writeable as $file) { 
        if (!is_writable(SITE_PATH . '/' . $file)) { 
            self::$errorsUnable to write to $file which is required."; 
        }
    }

}
}


