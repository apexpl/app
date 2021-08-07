<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use apex\app\cli\cli;
use apex\app\sys\install\{env_checks, in_apex, in_redis, in_rdbms, in_yaml};


/**
 * Main installer class.
 */
class installer
{


/**
 * Run wizard
 */
public static function run_wizard():void
{

    // Run checks
    env_checks::check();

    // Get instance info
    in_apex::get_instance_info();

    // Check for install.yml file
    if (file_exists(SITE_PATH . '/install.yml') || file_exists(SITE_PATH . '/install.yaml')) { 
        in_yaml::install();
    }

    // Setup redis
    in_redis::setup();

    // Setup mySQL, if not slave server
    in_rdbms::setup();

    // Complete install
    in_apex::complete_install();

    // Welcome message
    self::welcome_message();

}

/**
 * Welcome message
 */
public static function welcome_message():void
{

    // Initialize
    $admin_url = 'http://' . _config('core:domain_name') . '/admin/';

    // Output message
    cli::send("Thank you!  Apex has now been successfully installed on your server.\n");
    if (count(in_apex::$cron_jobs) > 0) { 
        cli::send("\nTo complete installation, please ensure the following crontab job is added.\n\n");
        foreach (in_apex::$cron_jobs as $job) { 
            cli::send("      $job\n");
        }
    }

    // Conclusion
    cli::send("\nYou may continue to your administration panel and create your first administrator by visiting:\n");
    cli::send("      $admin_url\n\n");
    cli::send("Thank you for choosing Apex.  You may view all documentation and training materials at https://apexpl.io/docs/\n");

}
/**
 * Installation error
 */
public static function install_error(string $message)
{
    cli::send("Error: $message\n\n");
    exit(0);
}


}

