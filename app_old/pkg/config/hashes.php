<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\pkg\pkg_components;
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;


/**
 * Handles hash package configuration.
 */
#[used_by(config::class)]
class hashes extends config


/**
 * Install hashes 
 */
protected static function install()
{ 

    // Debug
    debug::add(3, tr("Starting hashes install of package, {1}", $this->pkg_alias));

    // Add needed hashes
    $hashes = $this->pkg->hash ?? [];
    foreach ($hashes as $hash_alias => $vars) { 
        if (!is_array($vars)) { continue; }

        // Add / update hash
        $comp_alias = $this->pkg_alias . ':' . $hash_alias;
        redis::hset('hash', $comp_alias, json_encode($vars));
        pkg_component::add('hash', $comp_alias);

        // Check for var deletion
        $chk_vars = db::get_column("SELECT alias FROM internal_components WHERE type = 'hash_var' AND package = %s AND parent = %s", $this->pkg_alias, $hash_alias);
        foreach ($chk_vars as $chk) { 
            if (isset($vars[$chk])) { continue; }
            pkg_component::remove('hash_var', $comp_alias . ':' . $chk);
        }

        // Go through variables
        $order_num = 1;
        foreach ($vars as $key => $value) { 
            pkg_component::add('hash_var', $comp_alias . ':' . $key, $value, $order_num);
        $order_num++; }
    }

    // Check for deletions
    $chk_hash = db::get_column("SELECT alias FROM internal_components WHERE type = 'hash' AND package = %s", $this->pkg_alias);
    foreach ($chk_hash as $chk) { 
        if (isset($hashes[$chk])) { continue; }
        pkg_component::remove('hash', $this->pkg_alias . ':' . $chk);
        redis::hdel('hash', $this->pkg_alias . ':' . $chk);
    }

    // Debug
    debug::add(3, tr("Completed hashes install of package, {1}", $this->pkg_alias));

}

}


