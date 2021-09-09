<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\Cli\Cli;
use RedisException;
use redis as redisdb;

/**
 * Redis installer
 */
class RedisInstaller
{

    // Properties
        public static ?array $info = null;

    /**
     * Setup
     */
    public static function setup(Cli $cli):redisdb
    {

        // Get connection info
        $info = self::getConnectionInfo($cli);

        // Connect to redis
        $redis = self::connect($cli, $info);

        // Format and wipe keys, if needed
        self::format($cli, $redis);

        // Return
        return $redis;
    }

    /**
     * Get connection info
     */
    private static function getConnectionInfo(Cli $cli):array
    {

        // Check if already defined via Yaml installer
        if (self::$info !== null) { 
            return self::$info;
        }

        // Initialize
        $info = [
            'host' => 'localhost',
            'port' => 6379,
            'password' => '',
            'dbindex' => 0
        ];

        // Check for any passed connection fino
        $opt = $cli->getArgs(['redis-host', 'redis-port', 'redis-password', 'redis-dbindex']);
        foreach (['host', 'port', 'password', 'dbindex'] as $var) { 
            if (!isset($opt['redis-' . $var])) { 
                continue;
            }
            $info[$var] = $opt['redis-' . $var];
        }
        $redis_local = $opt['redis-local'] ?? false;

        // Prompt user
        if ($redis_local === false) {  
            $cli->sendHeader('redis Information');
            $info['host'] = $cli->getInput("Redis Host [localhost]:", 'localhost');
            $info['port'] = (int) $cli->getInput('Redis Port [6379]:', '6379');
            $info['password'] = $cli->getInput("Redis Password []:", '');
            $info['dbindex'] = (int) $cli->getInput('Redis DB Index [0]:', '0');
        }

        // Return
        return $info;
    }

    /**
     * Connect
     */
    private static function connect(Cli $cli, array $info):redisdb
    {

        // Connect to redis
        $redis = new redisdb();
        try { 
            $redis->connect($info['host'], (int) $info['port'], 2);
        } catch (RedisException $e) { 
            $cli->error("Unable to connect to redis database using supplied information.  Please check the host and port, and try the installer again.");
            exit(0);
        }

        // Redis authentication, if needed
        if ($info['password'] != '') { 

            try { 
                $redis->auth($info['password']);
            } catch (RedisException $e) { 
                $cli->error("Unable to authenticate to redis with the provided password.  Please check the password, and try the installer again.");
                exit(0);
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
     * Format redis instance
     */
    private static function format(Cli $cli, redisdb $redis):void
    {

        // Check if slave
        $opt = $cli->getArgs();
        $is_slave = $opt['save'] ?? false;
        if ($is_slave === true) { 
            return;
        }

        // Check for existing keys
        $keys = $redis->keys('*');
        if (count($keys) == 0) { 
            return;
        }

        // Confirm deletion of all redis keys
        if (!$cli->getConfirm('redis contains existing keys.  This will delete all existing keys within redis.  Are you sure you want to continue? (y/n) [n]' , 'n')) {  
            $cli->send('Ok, aborting install.  If this is a slave server / installation, please use the --slave option and run installation again.');
            exit(0);
        }

        // Empty redis database
        foreach ($keys as $key) { 
            $redis->del($key);
        }

    }

}

