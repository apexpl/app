<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\{Container, Db, Debugger};
use redis;

/**
 * Hashes
 */
class Hashes
{

    /**
     * Constructor
     */
    public function __construct(
        private string $pkg_alias, 
        private Db $db, 
        private redis $redis, 
        private ?Debugger $debugger = null
    ) { 

    }

    /**
     * Install
     */
    public function install(array $yaml):void
    {

        // Initialize
        $hashes = $yaml['hashes'] ?? [];
        $this->debugger?->add(2, "Starting install of hashes for package $this->pkg_alias");

        // Go through hashes
        foreach ($hashes as $hash_alias => $vars) { 
            if (!is_array($vars)) { continue; }

            // Add / update hash
            $comp_alias = $this->pkg_alias . '.' . $hash_alias;
            $this->redis->hset('config:hash', $comp_alias, json_encode($vars));
        }

        // Check for deletions
        $keys = $this->redis->hkeys('config:hash');
        foreach ($keys as $key) { 

            // Skip, if needed
            if (!preg_match("/^" . $this->pkg_alias . ".(.+)$/", $key, $match)) { 
                continue;
            } elseif (isset($hashes[$match[1]])) { 
                continue;
            }

            // Delete hash
            $this->redis->hdel('config:hash', $key);
        }

        // Debug
        $this->debugger?->add(2, "Completed install of hashes on package $this->pkg_alias");
    }

    /**
     * Remove
     */
    public function remove(array $yaml):void
    {

        // Delete from redis
        $keys = $this->redis->hkeys('config:hash');
        foreach ($keys as $key) { 

            if (!str_starts_with($key, $this->pkg_alias . '.')) { 
                continue;
            }
            $this->redis->hdel('config:hash', $key);
        }

    }

}

