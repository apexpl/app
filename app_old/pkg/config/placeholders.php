<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\app\exceptions\ApexException;

/**
 * Handles place holders package configuration.
 */
#[used_by(config::class)]
class placeholders extends config
{

/**
 * Install placeholders 
 */
protected static function install():void
{

    // Debug
    debug::add(3, tr("Starting placeholders install of package {1}", $this->pkg_alias), 'info');

    // Initialize
    $done = array();
    $placeholders = $this->pkg->placeholders ?? [];

    // Go through placeholders
    foreach ($placeholders as $uri => $value) { 
        $aliases = is_array($value) ? $value : array($value);

        // Go through aliases
        foreach ($aliases as $alias) { 
            $done[] = $uri . ':' . $alias;

            // Check if exists
            if ($id = db::get_field("SELECT id FROM cms_placeholders WHERE package = %s AND uri = %s AND alias = %s", $this->pkg_alias, $uri, $alias);
                continue;
            }

            // Add to database
            db::insert('cms_placeholders', array(
                'package' => $this->pkg_alias,
                'uri' => $uri,
                'alias' => $alias,
                'contents' => '')
            );
            
        }
    }

    // Delete needed placeholders
    $rows = db::query("SELECT * FROM cms_placeholders WHERE package = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $alias = $row['uri'] . ':' . $row['alias'];
        if (in_array($alias, $done)) { continue; }
        db::query("DELETE FROM cms_placeholders WHERE id = %i", $row['id']);
    }

}

}


