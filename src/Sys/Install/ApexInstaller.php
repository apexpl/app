<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\Svc\Di;
use Apex\App\App;
use Apex\App\Cli\Cli;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Pkg\Helpers\Migration;
use redis;

/**
 * Instance
 */
class ApexInstaller
{

    // Properties
    private static string $domain_name = 'localhost';
    private static string $server_type = 'all';
    private static string $instance_name = 'main';
    private static int $enable_admin = 1;
    public static array $cron_jobs = [];


    /**
     * Get instance info
     */
    public static function getInstanceInfo(Cli $cli):void
    {

    // Send header
    $cli->sendHeader('Apex Installation Wizard');

    // Check if slave
        $opt = $cli->getArgs();
        $is_slave = $opt['slave'] ?? false;

        // Get domain name, if not slave
        if ($is_slave === false) { 
            self::$domain_name = $cli->getInput('Domain Name [localhost]: ', 'localhost');
            return;
        }

        // Get server type
        $server_types = [
            'app' => 'Back-End Application Server',
            'web' => 'Front-End HTTP Server',
            'misc' => 'Other / Miscellanous'
        ];
        self::$server_type = $cli->getOption('Please specify the type of server instance this is:', 'web', true);

        // Get and check instance name
        self::$instance_name = strtolower($cli->get_input('Instance Name: '));
        if (self::$instance_name == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", self::$instance_name)) {
            $cli->error('Invalid instance name specified, which is required for slave servers.  This may be anything you wish such as app1, backend1, etc.');
            exit(0);
        }

        // Enable admin panel?
        $ok = $cli->getConfirm('Enable admin panel?', 'y');
        self::$enable_admin = $ok === true ? 1 : 0;
    }

    /**
     * Complete install
     */
    public static function complete(Cli $cli, redis $redis):App
    {

        // Initialize app
        $app = new App();

        // Check if slave server 
        $opt = $cli->getArgs();
        $is_slave = $opt['slave'] ?? false;
        if ($is_slave === false) { 
            self::installCore($redis, $app);
        }

        // Create .env file
        self::createEnvFile();

        // Add cron jobs
        self::addCrontab($cli);

        // Install any available upgrades
        //$upgrade_client = app::make(upgrade::class);
        //$upgrade_client->install('core');

        // Return
        return $app;
    }

    /**
     8 Install core
     */
    private static function installCore(redis $redis, App $app):void
    {

        // Load package
        $pkg_store = Di::make(PackagesStore::class);
        $pkg = $pkg_store->get('core');

        // Install core package
        // Install core package
        $migration = Di::make(Migration::class);
        $migration->install($pkg);

        // Update config as needed
        $app->setConfigVar('core.domain_name', self::$domain_name);
        $app->setConfigVar('core.encrypt_password', base64_encode(openssl_random_pseudo_bytes(32)));
    }

    /**
     * Create .env file
     */
    private static function createEnvFile():void
    {

        // Set replace vars
        $replace = [
            '~instance_name~' => self::$instance_name, 
            '~server_type~' => self::$server_type, 
            '~enable_admin~' => self::$enable_admin, 
            '~redis_host~' => getenv('redis_host'),
            '~redis_port~' => getenv('redis_port'),  
            '~redis_password~' => getenv('redis_password'), 
            '~redis_dbindex~' => getenv('redis_dbindex')
        ];

        // Get .env file contents
        $contents = base64_decode('CiMKIyBBcGV4IC5lbnYgZmlsZS4KIwojIEluIG1vc3QgY2FzZXMsIHlvdSBzaG91bGQgbmV2ZXIgbmVlZCB0byBtb2RpZnkgdGhpcyBmaWxlIGFzaWRlIGZyb20gdGhlIAojIHJlZGlzIGNvbm5lY3Rpb24gaW5mb3JtYXRpb24uICBUaGUgZXhjZXB0aW9uIGlzIGlmIHlvdSdyZSBydW5uaW5nIEFwZXggb24gIAojIGEgY2x1c3RlciBvZiBzZXJ2ZXJzLiAgVGhpcyBmaWxlIGFsbG93cyB5b3UgdG8gb3ZlcnJpZGUgdmFyaW91cyAKIyBzeXN0ZW0gY29uZmlndXJhdGlvbiB2YXJpYWJsZXMgZm9yIHRoaXMgc3BlY2lmaWMgc2VydmVyIGluc3RhbmNlLCBzdWNoIGFzIGxvZ2dpbmcgYW5kIGRlYnVnZ2luZyBsZXZlbHMuCiMKCiMgUmVkaXMgY29ubmVjdGlvbiBpbmZvcm1hdGlvbgpyZWRpc19ob3N0ID0gfnJlZGlzX2hvc3R+CnJlZGlzX3BvcnQgPSB+cmVkaXNfcG9ydH4KcmVkaXNfcGFzc3dvcmQgPSB+cmVkaXNfcGFzc3dvcmR+CnJlZGlzX2RiaW5kZXggPSB+cmVkaXNfZGJpbmRleH4KCiMgRW5hYmxlIGFkbWluIHBhbmVsPyAoMT1vbiwgMD1vZmYpCmVuYWJsZV9hZG1pbiA9IH5lbmFibGVfYWRtaW5+CgojIFRoZSBuYW1lIG9mIHRoaXMgaW5zdGFuY2UuICBDYW4gYmUgYW55dGhpbmcgeW91IHdpc2gsIAojIGJ1dCBuZWVkcyB0byBiZSB1bmlxdWUgdG8gdGhlIGNsdXN0ZXIuCjtpbnN0YW5jZV9uYW1lID0gfmluc3RhbmNlX25hbWV+CgojIFRoZSB0eXBlIG9mIGluc3RhbmNlLCB3aGljaCBkZXRlcm1pbmVzIGhvdyB0aGlzIGluc3RhbmNlIAojIG9wZXJhdGVzIChpZS4gd2hldGhlciBpdCBzZW5kcyBvciByZWNlaXZlcyByZXF1ZXN0cyB2aWEgUmFiYml0TVEpLgojCiMgU3VwcG9ydGVkIHZhbHVlcyBhcmU6CiMgICAgIGFsbCAgPSBBbGwtaW4tT25lIChkZWZhdWx0KQojICAgICB3ZWIgID0gRnJvbnQtZW5kIEhUVFAgc2VydmVyCiMgICAgIGFwcCAgPSBCYWNrLWVuZCBhcHBsaWNhdGlvbiBzZXJ2ZXIKIyAgICAgbWlzYyA9IE90aGVyCiMKO3NlcnZlcl90eXBlID0gfnNlcnZlcl90eXBlfgoKIyBTZXJ2ZXIgbW9kZSwgY2FuIGJlICdwcm9kJyBvciAnZGV2ZWwnCjttb2RlID0gZGV2ZWwKCiMgTG9nIGxldmVsLiAgU3VwcG9ydGVkIHZhbHVlcyBhcmU6CiMgICAgIGFsbCA9IEFsbCBsb2cgbGV2ZWxzCiMgICAgIG1vc3QgPSBBbGwgbGV2ZWxzLCBleGNlcHQgJ2luZm8nIGFuZCAnbm90aWNlJy4KIyAgICAgZXJyb3Jfb25seSA9IE9ubHkgZXJyb3IgbWVzc2FnZXMKIyAgICAgbm9uZSA9IE5vIGxvZ2dpbmcKO2xvZ19sZXZlbCA9IG1vc3QKCiMgRGVidWcgbGV2ZWwuICBTdXBwb3J0ZWQgdmFsdWVzIGFyZToKIyAgICAgMCA9IE9mZgojICAgICAxID0gVmVyeSBsaW1pdGVkCiMgICAgIDIgPSBMaW1pdGVkCiMgICAgIDMgPSBNb2RlcmF0ZQojICAgICA0ID0gRXh0ZW5zaXZlCiMgICAgIDUgPSBWZXJ5IEV4dGVuc2l2ZQo7ZGVidWdfbGV2ZWwgPSAwCgoK');
        $contents = strtr($contents, $replace);

        // Save .env file
        file_put_contents(SITE_PATH . '/.env', $contents);
    }

    /**
     * Add crontab jobs
     */
    private static function addCrontab(Cli $cli):void
    {

        // Determine cron jobs based on server type
        if (self::$server_type != 'web') { 
            self::$cron_jobs[] = "* * * * * " . SITE_PATH . "/apex crontab > /dev/null 2>&1";
        }

        // Return, if necessary
        if (count(self::$cron_jobs) == 0 || !function_exists('system')) { 
            return;
        }

        // List cron jobs to enable
        $cli->send("The following crontab jobs should be added to your server:\r\n\r\n");
        foreach (self::$cron_jobs as $job) { 
            $cli->send("    $job\n");
        }

        // Ask to automatically add jobs
        if (!$cli->getConfirm("\nWould you like the installer to automatically add them? (y/n) [n]: ", 'n')) { 
            return;
        }

        // Add crontab jobs
        foreach (self::$cron_jobs as $job) { 
            system ("(crontab -l; echo \"$job\";) | crontab");
        }
        self::$cron_jobs = [];
    }

}


