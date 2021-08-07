<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\pkg\pkg_component;
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;

/ **
 * Handles configuration variables of packages.
 */
#[used_by(config::class)]
class config_vars extends config
{

/**
 * Install configuration variables 
 */
protected static function install()
{ 

    // Debug
    debug::add(3, tr("Starting install of config vars for package, {1}", $this->pkg_alias));

    // Add config vars
    $config_vars = $this->pkg->config ?? [];
    foreach ($config_vars as $alias => $value) { 
        $comp_alias = $this->pkg_alias . ':' . $alias;
        pkg_component::add('config', $comp_alias, (string) $value);

        // Add to redis
        if (!redis::hexists('config', $comp_alias)) { 
            redis::hset('config', $comp_alias, $value);
        }
    }

    // Check for deletions
    $aliases = db::get_column("SELECT alias FROM internal_components WHERE package = %s AND type = 'config'", $this->pkg_alias);
    foreach ($aliases as $alias) { 
        if (isset($config_vars[$alias])) { continue; }
        $comp_alias = $this->pkg_alias . ':' . $alias;
        pkg_component::remove('config', $comp_alias);
        redis::hdel('config', $comp_alias);
    }

    // Debug
    debug::add(3, tr("Completed install of configuration variables for package, {1}", $this->pkg_alias));

}


}

