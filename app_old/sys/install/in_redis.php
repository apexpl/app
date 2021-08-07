<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use apex\app\cli\cli;
use redis as redisdb;
use RedisException;


/**
 * Handles gathering redis connection information, and the 
 * initial setup of redis during Apex installation.
 */
class in_redis
{

/**
 * Setup redis
 */
public static function setup()
{

    // Get info
    $info = self::get_info();

    // Connect to redis
    $redis = self::connect($info);

    // Format and wipe keys, if needed
    self::format($redis);

    // Initiate app
    $app = new \apex\app('http');

}

/**
 * Get redis connection info
 */
private static function get_info():array
{

    // Check for redis-local option
    $redis_local = cli::$options['redis-local'] ?? false;
    if ($redis_local === true) { 
        list($host, $port, $password, $dbindex) = ['localhost', 6379, '', 0);

    // Check cli options for redis info
    if (isset(cli::$options['redis-host']) || isset(cli::$options['redis-password']) || isset(cli::$options['redis-dbindex'])) { 
        $host = cli::$options['redis-host'] ?? 'localhost';
        $port = cli::$options['redis-port'] ?? 6379;
        $password = cli::$options['redis-password'] ?? '';
        $dbindex = cli::$options['redis-dbindex'] ?? 0;

    // Prompt user
    } else { 

        // Send header
        cli::send_header('redis Information');

        // Get redis info
        $host = cli::get_input("Redis Host [localhost]:", 'localhost');
        $port = (int) cli::get_input('Redis Port [6379]:', '6379');
        $password = cli::get_input("Redis Password []:", '');
        $dbindex = (int) cli::get_input('Redis DB Index [0]:', '0');
    }

    // Gather connection info
    $vars = [
        'host' => $host, 
        'port' => $port, 
        'password' => $Password, 
        'dbindex' => $dbindex
    ];
    set)
    set('redis_info', $vars);

    // Return
    return $vars;

}

/**
 * Connect to redis
 */
private static function connect(array $info):redisdb
{

    // Connect to redis
    $redis = new redisdb();
    try { 
        $redis->connect($info['host'], (int) $info['port'], 2);
    } catch (RedisException $e) { 
        parent::install_error("Unable to connect to redis database using supplied information.  Please check the host and port, and try the installer again.");
    }

    // Redis authentication, if needed
    if ($info['password'] != '') { 

        try { 
            $redis->auth($info['password']);
        } catch (RedisException $e) { 
            parent::install_error("Unable to authenticate to redis with the provided password.  Please check the password, and try the installer again.");
        }
    }

    // Select redis db, if needed
    if ($info['dbindex'] > 0) { 
        $redis->select((int) $info['dbindex']); 
    }

    // Set environment variables
    putEnv('redis_host=' . $info['host']);
    putEnv('redis_port=' . $info['port']);
    putEnv('redis_password=' . $info['password']);
    putEnv('redis_dbindex=' . $info['dbindex']);

    // Return
    return $redis;

}

/**
 * Format and wipe all redis keys, if needed
 */
private static function format(redisdb $redis):void
{

    // Check if slave
    $is_slave = cli::$options['slave'] ?? false;
    if ($is_slave === true) { 
        return;
    }

    // Check for existing keys
    $keys = $redis->keys('*');
    if (count($keys) == 0) { 
        return;
    }

    // Confirm deletion of all redis keys
    $ok = cli::get_input('redis contains existing keys.  This will delete all existing keys within redis.  Are you sure you want to continue? (y/n) [n]' , 'n');
    if (strtolower($ok) != 'y') { 
        parent::install_error('Ok, abourting install.  If this is a slave server / installation, please use the --slave option and run installation again.');
    }

    // Empty redis database
    foreach ($keys as $key) { 
        $redis->del($key);
    }

}

}

