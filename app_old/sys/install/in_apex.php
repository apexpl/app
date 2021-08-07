<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use apex\libc\{db, redis, io};
use apex\app\cli\cli;
use apex\app\pkg\config\config;
use apex\app\pkg\pkg_components;


/**
 * Handles the Apex specific installation aspects such as 
 * the .env file, setup of Apex database tables, 
 * and other configuration.
 */
class in_apex
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
public static function get_instance_info():void
{

    // Send header
    cli::send_header('Apex Installation Wizard');

    // Check if slave
    $is_slave = cli::$options['slave'] ?? false;
    if ($is_slave === false) { 
        self::$domain_name = cli::get_input('Domain Name [localhost]: ', 'localhost');
        return;
    }

    // Check if cluster package installed
    if (!check_package('cluster')) { 
        parent::install_error(The cluster package is required to create slave instances and utilize horizontal scaling.  Please install the package first with the command:  apex install cluster');
    }

    // Display available server types
    cli::send("Server Types:\n\n");
    cli::send("    [all]  All in One Server\n";
    cli::send("    [web]  Frontend HTTP Server\n");
    cli::send("    [app]  Backend Application Server\n");
    cli::send("    [misc] Other / Miscellaneous\n\n");

    // Get server type
    $ok=false;
    do {
        self::$server_type = cli::get_input('Type of Instance? [all]: ', 'all');
        if (!in_array(self::$server_type, ['all','web','app','misc'])) {
            cli::send("Invalid server type.  Please select a valid server type.\n\n");
        }
        $ok = true;
    } while ($ok === false; }

    // Get and check instance name
    self::$instance_name = strtolower(cli::get_input('Instance Name: '));
    if (self::$instance_name == '') { 
        parent::install_error('You must specify an instance name for slave servers.  This may be anything you wish such as app1, backend1, etc.');
    }

    // Enable admin panel?
    $ok = cli::get_input('Enable admin panel? (y/n) [y]: ', 'y');
    self::$enable_admin = strtolower($ok) == 'y' ? 1 : 0;

}

/**
 * Complete install
 */
public static function complete_install():void
{

    // Check if slave server 
    $is_slave = cli::$options['slave'] ?? false;
    if ($is_slave === false) { 

        // Install core package
        self::install_core();

        // Install components
        self::install_components();

        // Finalize install
        self::finalize();
    }

    // Create .env file
    self::create_env_file();

    // Add cron jobs
    self::add_crontab();

    // Install any available upgrades
    //$upgrade_client = app::make(upgrade::class);
    //$upgrade_client->install('core');

}

/**
 * Install core package
 */
private static function install_core():void
{

    // Check if slave
    $is_slave = cli::$options['slave'] ?? false;
    if ($is_slave === true) { 
        return;
    }

    // Load package
    $config = app::make(config::class, ['pkg_alias' => 'core']);

    // Install configuration
    $config->initial_install();

    // Update version
    db::query("UPDATE internal_packages SET version = %s WHERE alias = 'core'", $config->pkg->version);

    // CHMOD directories
    chmod('./storage/logs', 0777);
    chmod('./apex', 0755);

}

/**
 * Install components
 */
private static function install_components()
{

    // Install components
    $files = io::parse_dir(SITE_PATH . '/src/core');
    foreach ($files as $file) { 
        pkg_component::add_from_filename('core', "src/$file");
    }

    // Add views
    $views = io::parse_dir(SITE_PATH . '/views/tpl');
    foreach ($views as $view) { 
        pkg_component::add_from_filename('core', "views/$file");
    }

}

/**
 * Finalize install
 */
public static function finalize():void
{

    // Update redis config
    app::update_config_var('core:db_driver', $this->db_driver);
    app::update_config_var('core:cookie_name', io::generate_random_string(12));
    app::update_config_var('core:domain_name', self::$domain_name);

    // Set encryption info
    app::update_config_var('core:encrypt_cipher', 'aes-256-cbc');
    app::update_config_var('core:encrypt_password', base64_encode(openssl_random_pseudo_bytes(32)));
    app::update_config_var('core:encrypt_iv', io::generate_random_string(16));

}

/**
 * Create .env file
 */
private static function create_env_file():void
{

    // Get text
    $env_text = base64_decode('CiMKIyBBcGV4IC5lbnYgZmlsZS4KIwojIEluIG1vc3QgY2FzZXMsIHlvdSBzaG91bGQgbmV2ZXIgbmVlZCB0byBtb2RpZnkgdGhpcyBmaWxlIGFzaWRlIGZyb20gdGhlIAojIHJlZGlzIGNvbm5lY3Rpb24gaW5mb3JtYXRpb24uICBUaGUgZXhjZXB0aW9uIGlzIGlmIHlvdSdyZSBydW5uaW5nIEFwZXggb24gIAojIGEgY2x1c3RlciBvZiBzZXJ2ZXJzLiAgVGhpcyBmaWxlIGFsbG93cyB5b3UgdG8gb3ZlcnJpZGUgdmFyaW91cyAKIyBzeXN0ZW0gY29uZmlndXJhdGlvbiB2YXJpYWJsZXMgZm9yIHRoaXMgc3BlY2lmaWMgc2VydmVyIGluc3RhbmNlLCBzdWNoIGFzIGxvZ2dpbmcgYW5kIGRlYnVnZ2luZyBsZXZlbHMuCiMKCiMgUmVkaXMgY29ubmVjdGlvbiBpbmZvcm1hdGlvbgpyZWRpc19ob3N0ID0gfnJlZGlzX2hvc3R+CnJlZGlzX3BvcnQgPSB+cmVkaXNfcG9ydH4KcmVkaXNfcGFzc3dvcmQgPSB+cmVkaXNfcGFzc34KcmVkaXNfZGJpbmRleCA9IH5yZWRpc19kYmluZGV4fgoKIyBFbmFibGUgYWRtaW4gcGFuZWw/ICgxPW9uLCAwPW9mZikKZW5hYmxlX2FkbWluID0gfmVuYWJsZV9hZG1pbn4KCiMgVGhlIG5hbWUgb2YgdGhpcyBpbnN0YW5jZS4gIENhbiBiZSBhbnl0aGluZyB5b3Ugd2lzaCwgCiMgYnV0IG5lZWRzIHRvIGJlIHVuaXF1ZSB0byB0aGUgY2x1c3Rlci4KO2luc3RhbmNlX25hbWUgPSBtYXN0ZXIKCiMgVGhlIHR5cGUgb2YgaW5zdGFuY2UsIHdoaWNoIGRldGVybWluZXMgaG93IHRoaXMgaW5zdGFuY2UgCiMgb3BlcmF0ZXMgKGllLiB3aGV0aGVyIGl0IHNlbmRzIG9yIHJlY2VpdmVzIHJlcXVlc3RzIHZpYSBSYWJiaXRNUSkuCiMKIyBTdXBwb3J0ZWQgdmFsdWVzIGFyZToKIyAgICAgYWxsICA9IEFsbC1pbi1PbmUgKGRlZmF1bHQpCiMgICAgIHdlYiAgPSBGcm9udC1lbmQgSFRUUCBzZXJ2ZXIKIyAgICAgYXBwICA9IEJhY2stZW5kIGFwcGxpY2F0aW9uIHNlcnZlcgojICAgICBtaXNjID0gT3RoZXIKIwo7c2VydmVyX3R5cGUgPSBhbGwKCiMgU2VydmVyIG1vZGUsIGNhbiBiZSAncHJvZCcgb3IgJ2RldmVsJwo7bW9kZSA9IGRldmVsCgojIExvZyBsZXZlbC4gIFN1cHBvcnRlZCB2YWx1ZXMgYXJlOgojICAgICBhbGwgPSBBbGwgbG9nIGxldmVscwojICAgICBtb3N0ID0gQWxsIGxldmVscywgZXhjZXB0ICdpbmZvJyBhbmQgJ25vdGljZScuCiMgICAgIGVycm9yX29ubHkgPSBPbmx5IGVycm9yIG1lc3NhZ2VzCiMgICAgIG5vbmUgPSBObyBsb2dnaW5nCjtsb2dfbGV2ZWwgPSBtb3N0CgojIERlYnVnIGxldmVsLiAgU3VwcG9ydGVkIHZhbHVlcyBhcmU6CiMgICAgIDAgPSBPZmYKIyAgICAgMSA9IFZlcnkgbGltaXRlZAojICAgICAyID0gTGltaXRlZAojICAgICAzID0gTW9kZXJhdGUKIyAgICAgNCA9IEV4dGVuc2l2ZQojICAgICA1ID0gVmVyeSBFeHRlbnNpdmUKO2RlYnVnX2xldmVsID0gMAoKCg==');

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
private static function add_crontab():void
{

    // Determine cron jobs based on server type
    if (self::$server_type == 'all') { 
        self::$cron_jobs[] = "*/2 * * * * cd " . SITE_PATH . "; /usr/bin/php -q apex core.cron > /dev/null 2>&1";
    } elseif (self::$server_type == 'app') { 
        self::$cron_jobs[] = "*/2 * * * * cd " . SITE_PATH . "; /usr/bin/php -q apex cluster.listen > /dev/null 2>&1";
    }

    // Return, if necessary
    if (count(self::$cron_jobs) == 0 || !function_exists('system')) { 
        return;
    }

    // List cron jobs to enable
    cli::send("The following crontab jobs should be added to your server:\n\n");
    foreach (self::$cron_jobs as $job) { 
        cli::send("    $job\n");
    }

    // Ask to automatically add jobs
    $ok = cli::get_input("\nWould you like the installer to automatically add them? (y/n) [n]: ", 'n');
    if (strtolower($ok) != 'y') {
        return;
    }

    // Add crontab jobs
    foreach (self::$cron_jobs as $job) { 
        system ("(crontab -l; echo \"$job\";) | crontab");
    }
    self::$cron_jobs = [];

}

}


