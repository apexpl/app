<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use redis;

/**
 * Services
 */
class Services
{

    /**
     * Constructor
     */
    public function __construct(
        private string $pkg_alias,
        private redis $redis
    ) { 

    }

    /**
     * Install
     */
    public function install(array $registry):void
    {

        // Check for services
        $services = $registry['services'] ?? [];
        foreach ($services as $class_name) { 
            $this->redis->hset('config:services', $class_name, $this->pkg_alias);
        }

    }

    /**
     * Remove
     */
    public function remove(array $registry):void
    {

        // Go through redis servers
        $services = $this->redis->hgetall('config:services') ?? [];
        foreach ($services as $class_name => $alias) { 

            // Skip, if not correct package
            if ($alias != $this->pkg_alias) { 
                continue;
            }
            $this->redis->hdel('config:services', $class_name);
        }

    }

}


