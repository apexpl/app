<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\App;
use Apex\App\Cli\Cli;

/**
 * Installer
 */
class Installer
{

    /**
     * Run
     */
    public static function run():void
    {

        // Environment checks
        $cli = new Cli;
        EnvChecks::check($cli);

        // Get instance info
        ApexInstaller::getInstanceInfo($cli);

    // Setup redis
        $redis = RedisInstaller::setup($cli);

        // Setup database
        DatabaseInstaller::setup($cli, $redis);

        // Complete install
        $app = ApexInstaller::complete($cli, $redis);

        // Welcome message
        self::welcomeMessage($cli, $app);

        // Exit
        exit(0);
    }

    /**
     * Welcome message
     */
    private static function welcomeMessage($cli, App $app):void
    {

        // Initialize
        $admin_url = 'http://' . $app->config('core.domain_name') . '/admin/';

        // Output message
        $cli->send("Thank you!  Apex has now been successfully installed on your server.\n");
        if (count(ApexInstaller::$cron_jobs) > 0) { 
            $cli->send("\nTo complete installation, please ensure the following crontab job is added.\n\n");
            foreach (ApexInstaller::$cron_jobs as $job) { 
                $cli->send("      $job\n");
            }
        }

        // Conclusion
        $cli->send("\nYou may continue to your administration panel and create your first administrator by visiting:\n");
        $cli->send("      $admin_url\n\n");
        $cli->send("Thank you for choosing Apex.  You may view all documentation and training materials at https://apexpl.io/docs/\n");
    }

}


