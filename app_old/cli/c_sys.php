<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\lib\{db, redis, debug};
use apex\app\cli\cli;
use apex\app\pkg\package;
use apex\app\exceptions\ApexException;


/**
 * Handles various system commands for the Apex CLI client.
 */
class c_sys
{


/**
 * Debug
 */
public function debug(array $vars):string
{ 

    // Update config
    app::update_config_var('core:debug', $vars[0]);
    debug::add(4, tr("CLI: Updated debug mode to {1}", $vars[0]), 'info');

    // Return
    return "Successfully changed debugging mode to $vars[0]\n";

}

/**
 * Change server mode
 */
public function mode(array $vars):string
{ 

    // CHeck
    $mode = strtolower($vars[0]);
    if ($mode != 'devel' && $mode != 'prod') { 
        throw new ApexException('error', "You must specify the mode as either 'devel' or 'prod'");
    }

    // Update config
    app::update_config_var('core:mode', $mode);
    if (isset($vars[1])) { 
        app::update_config_var('core:debug_level', $vars[1]);
    }
    $level = $vars[1] ?? app::_config('core:debug_level');
    debug::add(4, tr("CLI: Updated server mode to {1}, debug level to {2}", $mode, $level), 'info');

    // Return
    return "Successfully updated server mode to $mode, and debug level to $level\n";

}

/**
 * Reset redis
 */
public function reset_redis(array $vars):string
{

    // Go through packages
    $packages = db::get_column("SELECT alias FROM internal_packages");
    foreach ($packages as $alias) { 

        // Ensure config.php file exists
        if (!file_exists(SITE_PATH . '/etc/' . $alias . '/config.php')) { 
            continue; 
        }

        $config = make(config::class, ['pkg_alias' => $alias]);
        if (!method_exists($config->pkg, 'reset_redis')) { 
            continue;
        }
        $config->pkg->reset_redis();
    }

    // Return
    return "Successfully reset redis and all keys\n";

}

/**
 * Update master SQL database connection info.
 */
public function update_masterdb(array $vars):string
{ 

    // Get optoins
    list($args, $options) = cli::get_args([
        'dbname', 
        'dbuser', 
        'dbpassword', 
        'dbhost', 
        'dbport'
    ]);

    // Check for options
    if (isset($options['dbname']) && isset($options['dbuser'])) { 
        $dbname = $options['dbname'];
        $dbuser = $options['dbuser'];
        $dbpass = $options['dbpassword'] ?? '';
        $dbhost = $options['dbhost'] ?? 'localhost';
        $dbport = $options['dbport'] ?? 3306;
    
    // User input
    } else { 
        $dbname = cli::get_input('Database Name: ');
        $dbuser = cli::get_input('Database Username: ');
        $dbpass = cli::get_input('Database Password []: ', '');
        $dbhost = cli::get_input('Database Host [localhost]: ', 'localhost');
        $dbport = (int) cli::get_input('Database Port [3306]: ', '3306');
    }

    // Set vars
    $vars = [
        'dbname' => $dbname,
        'dbuser' => $dbuser,
        'dbpass' => $dbpass,
        'dbhost' => $dbhost,
        'dbport' => $dbport,
        'dbuser_readonly' => '', 
        'dbpass_readonly' => ''
    ];

    // Update redis
    redis::del('config:db_master');
    redis::hmset('config:db_master', $vars);
    debug::add(4, "Updated master database connection information", 'info');

    // Return
    return "Successfully updated master database information.\n";

}

/**
 * Clear all db slave servers 
 */
public function clear_dbslaves()
{ 

    // Delete
    redis::del('config:db_slaves');
    debug::add(4, "CLI: Removed all database slave servers", 'info');

    // Return
    return "Successfully cleared all database slave servers.\n";

}

/**
 * Update RabbitMQ connection info.
 */
public function update_rabbitmq(array $vars):string
{ 

    // Get options
    list($args, $options) = cli::get_args([
        'host', 
        'user', 
        'password', 
        'port'
    ]);

    // Check options
    if (isset($options['host']) && isset($options['user'])) { 
        $host = $options['host'];
        $user = $options['user'];
        $pass = $options['password'] ?? '';
        $port = $options['port'] ?? 5672;

    // Get user input
    } else { 
        $host = cli::get_input('Host [localhost]: ', 'localhost');
        $port = cli::get_input('Port [5672]: ', '5672');
        $user = cli::get_input('Username [guest]: ', 'guest');
        $pass = cli::get_input('Password [guest]: ', 'guest');
    }

    // Set vars
    $vars = [
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'port' => $port
    ];

    // Update redis
    redis::del('config:rabbitmq');
    redis::hmset('config:rabbitmq', $vars);
    debug::add(4, "CLI: Updated RabbitMQ connection information", 'info');

    // Return
    return "Successfully updated RabbitMQ connection information.\n";

}

/**
 * Enable / disable cache
 */
public function cache(array $vars):string
{

    // Update cache
    $mode = isset($vars[0]) && strtolower($vars[0]) == 'on' ? 1 : 0;
    app::update_config_var('core:cache', $mode);

    // Return
    $action = $mode == 1 ? 'enabled' : 'disabled';
    return "Successfully $actopm the cache.";

}

/**
 * Compile core package.
 */
public function compile_core(array $vars):string
{ 

    // Compile
    $client = make(package::class);
    $destdir = $client->compile_core();
    debug::add(4, "CLI: Compiled core Apex framework", 'info');

    // Return
    return  "Successfully compiled the core Apex framework, and it is located at $destdir\n\n";

}

}

