<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\{Container, Db, Debugger};
use Apex\App\Attr\Inject;
use redis;

/**
 * Package config - config vars
 */
class ConfigVars
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
        $config_vars = $yaml['config'] ?? [];
        $this->debugger?->add(2, "Starting install of config vars for package $this->pkg_alias");

        // GO through config vars
        foreach ($config_vars as $alias => $value) { 
            $comp_alias = $this->pkg_alias . '.' . $alias;
            $config_vars[$alias] = $value;

            // Skip if exists
            if ($row = $this->db->getRow("SELECT * FROM internal_config WHERE package = %s AND alias = %s", $this->pkg_alias, $alias)) { 
                continue;
            }

            // ADd to db
            $this->db->insert('internal_config', [
                'package' => $this->pkg_alias, 
                'alias' => $alias, 
                'value' => $value
            ]);

            // Add to redis
            if (!$this->redis->hexists('config', $comp_alias)) { 
                $this->redis->hset('config', $comp_alias, $value);
            }
        }

        // Check for deletions
        $aliases = $this->db->getColumn("SELECT alias FROM internal_config WHERE package = %s", $this->pkg_alias);
        foreach ($aliases as $alias) { 

            // Skip if ok
            if (array_key_exists($alias, $config_vars)) {
                continue;
            }

            // Delete
            $comp_alias = $this->pkg_alias . ':' . $alias;
            $this->db->query("DELETE FROM internal_config WHERE package = %s AND alias = %s", $this->pkg_alias, $alias);
            $this->redis->hdel('config', $comp_alias);
        }

        // Debug
        $this->debugger?->add(2, "Completed install of config vars for package $this->pkg_alias");
    }

    /**
     * Remove
     */
    public function remove(array $yaml):void
    {

        // Delete from redis
        $keys = $this->redis->hkeys('config');
        foreach ($keys as $key) { 

            if (!str_starts_with($key, $this->pkg_alias . '.')) { 
                continue;
            }
            $this->redis->hdel('config', $key);
        }

        // Delete from db
        $this->db->query("DELETE FROM internal_config WHERE package = %s", $this->pkg_alias);
    }


}



