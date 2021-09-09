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


        // Intialize Cli class
        global $argv;
        $cli = new Cli;
        $cli->orig_argv = $argv;

        // Environment checks
        EnvChecks::check($cli);

        // Check for yaml file
        if (file_exists(SITE_PATH . '/install.yml') || file_exists(SITE_PATH . '/install.yaml')) { 
            YamlInstaller::process();
            return;
        }

        // Get instance info
        ApexInstaller::getInstanceInfo($cli);

    // Setup redis
        $redis = RedisInstaller::setup($cli);

        // Setup database
        DatabaseInstaller::setup($cli, $redis);

        // Complete install
        $app = ApexInstaller::complete($cli, $redis);

        // Check for installation image
        $opt = $cli->getArgs(['image']);
        if (isset($opt['image']) && $opt['image'] != '') { 
            ImageInstaller::install($opt['image'], $app, $cli);
        }

        // Welcome message
        self::welcomeMessage($cli, $app);

        // Exit
        exit(0);
    }

    /**
     * Welcome message
     */
    public static function welcomeMessage($cli, App $app):void
    {

        // Initialize
        $admin_url = 'http://' . $app->config('core.domain_name') . '/admin/';

        // Output message
        $cli->sendHeader('Installation Successful');
        $cli->send("Thank you!  Apex has now been successfully installed on your server.\r\n");
        if (count(ApexInstaller::$cron_jobs) > 0) { 
            $cli->send("\r\nTo complete installation, please ensure the following crontab job is added.\r\n\r\n");
            foreach (ApexInstaller::$cron_jobs as $job) { 
                $cli->send("      $job\n");
            }
        }
        $cli->send("\r\n");

        // Conclusion
        $cli->send("If the 'webapp' package is installed, you may continue to your administration panel and create your first administrator by visiting:\r\n\r\n");
        $cli->send("      $admin_url\r\n\r\n");
        $cli->send("Thank you for choosing Apex.  You may view all documentation and training materials at https://apexpl.io/docs/\r\n\r\n");
    }

}


