<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\Cli\Cli;

/**
 * Environment checks
 */
class EnvChecks
{

    // Properties
    private static string $php_min_version = '8.0.0';
    private static array $errors = [];

    // Required PHP extensions
    private static array $php_extensions = [
        'pdo', 
        'openssl', 
        'curl', 
        'json', 
        'mbstring', 
        'redis', 
        'gd',
        'zip'
    ];

    // Files / directories that must be writeable.
    private static array $writeable = [
        '.env',    
        'storage/',
        'storage/logs' 
    ];

    /**
     * Check environment
     */
    public static function check(Cli $cli):void
    {

        // Ensure we're on CLI
        if (php_sapi_name() != "cli") { 
            die("This system is not yet installed.  Please run via CLI to initiate the installer.");
        }

        // Get command line arguments
        $opt = $cli->getArgs([
            'redis-host', 
            'redis-password', 
            'redis-dbindex', 
            'dbname', 
            'dbuser', 
            'dbpassword', 
            'dbhost', 
            'dbport'
        ]);

        // Check PHP
        self::checkPhp();

        // Check filesystem
        self::checkFilesystem();

        // Display errors, if needed
        if (count(self::$errors) > 0) { 
            $cli->send("One or more errors were found.  Please resolve the below issues before continuing:\r\n\r\n");
            foreach (self::$errors as $msg) { 
                $cli->send("    - $msg\r\n");
            }
            exit(0);
        }

    }

    /**
     * Check PHP
     */
    private static function checkPhp():void
    {

        // Check PHP version
        if (version_compare(phpversion(), self::$php_min_version, '<') === true) { 
            self::$errors[] = "Apex requires PHP v" . self::$php_min_version . ".  Please upgrade your PHP installation before continuing.";
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
    private static function checkFilesystem():void
    {

        // Define required directories
        $req_dirs = [
            'storage/logs' 
        ];

        // Create required directories
        foreach ($req_dirs as $dir) { 
            if (is_dir(SITE_PATH . '/' . $dir)) { 
                continue; 
            }
            mkdir(SITE_PATH . '/' . $dir, 0777, true);
        }

        // Check writable files / dirs
        foreach (self::$writeable as $file) { 
            if (!is_writable(SITE_PATH . '/' . $file)) { 
                self::$errors[] = "Unable to write to $file which is required."; 
            }
        }

    }

}

