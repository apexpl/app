<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, redis, debug, components};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\app\exceptions\ApexException;


/**
 * Handles workers package configuration.
 */
#[used_by(config::class)]
class workers extends config
{


/**
 * Scan workers
 */
public static function scan():void
{

    // Debug
    debug::add(2, tr("Starting to scan workers for package {1}", $this->pkg_alias));

    // Go through all worker components
    $rows = db::query("SELECT * FROM internal_components WHERE package = %s AND type = 'worker' ORDER BY id", $this->pkg_alias);
    foreach ($rows as $row) { 
        $alias = $row['package'] . ':' . $row['alias'];

        // Get the routing key
        if (!$worker = components::load('worker', $row['alias'], 'core')) { 
            continue;
        }
        $routing_key = $worker->routing_key ?? $row['value'];

        // Update redis, as necessary
        $redis_key = 'config:worker:' . $routing_key;
        if (!redis::sismember($redis_key, $alias)) { 
            redis::sadd($redis_key, $alias);
        }
    }

    // Clean up all workers
    $keys = redis::keys("config:worker:*");
    foreach ($keys as $key) { 

        // Go through workers
        $workers = redis::smembers($key);
        foreach ($workers as $worker_alias) { 
            list($package, $alias) = explode(':', $worker_alias, 2);
            if (!file_exists(SITE_PATH . '/src/' . $package . '/worker/' . $alias . '.php')) { 
                redis::srem($key, $worker_alias);
            }
        }
    }

    // Debug
    debug::add(2, tr("Completed scanning workers for package {1}", $this->pkg_alias));

}

}


