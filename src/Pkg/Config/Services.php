<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\App\Attr\Inject;
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
    public function install(array $yaml):void
    {

        // Check for services
        $services = $yaml['services'] ?? [];
        foreach ($services as $class_name) { 
            $this->redis->hset('config:services', $class_name, $this->pkg_alias);
        }

    }

    /**
     * Remove
     */
    public function remove(array $yaml):void
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


