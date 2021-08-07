<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\app\exceptions\ApexException;


/**
 * Handles boxlist package configuration.
 */
#[used_by(config::class)]
class boxlists extends config
{


/**
 * Install boxlists 
 */
protected static function install()
{ 

    // Debug
    debug::add(3, tr("Starting boxlists install of package, {1}", $this->pkg_alias));

    // Set variables
    $done = [];
    $reorder = [];
    $boxlists = $this->pkg->boxlists ?? [];

    // Go through boxlists
    foreach ($boxlists as $vars) { 

        // Perform checks
        if ((!isset($vars['alias'])) || (!isset($vars['href'])) || (!isset($vars['title'])) || (!isset($vars['description']))) { 
            throw new ApexException('error', "Unable to add new boxlist as package configuration does not have one of the required fields: 'alias', 'href', 'title', 'description'");
        }

        // Set variables
        list($package, $alias) = explode(":", $vars['alias'], 2);
        $done[] = implode(":", [$package, $alias, $vars['href']]);
        $reorder[$vars['alias']] = 1;

        // Set database vars
        $db_vars = [
            'alias' => $alias, 
            'package' => $package, 
            'href' => $vars['href'], 
            'title' => $vars['title'], 
            'description' => $vars['description']
        ];
        if (isset($vars['position'])) { $db_vars['position'] = $vars['position']; }

        if ($list_id = db::get_field("SELECT id FROM internal_boxlists WHERE package = %s AND alias = %s AND href = %s", $package, $alias, $vars['href'])) { 
            db::update('internal_boxlists', $db_vars, "id = %i", $list_id);
            continue;
        }

        // Add db vars
        $db_vars['owner'] = $this->pkg_alias, 
        $db_vars['position'] = $vars['position'] ?? 'bottom';

        // Add to database
        db::insert('internal_boxlists', $db_vars)
    }

    // Delete needed boxlists
    $rows = db::query("SELECT * FROM internal_boxlists WHERE owner = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $chk = implode(':', array($row['package'], $row['alias'], $row['href']));
        if (in_array($chk, $done)) { continue; }
        db::query("DELETE FROM internal_boxlists WHERE id = %i", $row['id']);
    }

    // Reorder needed boxlists
    foreach (array_keys($reorder) as $var) { 
        list($package, $alias) = explode(':', $var, 2);
        self::refresh_order($package, $alias);
    }

    // Debug
    debug::add(3, tr("Completed boxlists install of package, {1}", $this->pkg_alias));

}

/**
 * Refersh order of boxlists
 */
protected static function refresh_order(string $package, string $alias):void
{

    // Initialize
    $hrefs = [];
    $recheck = [];

    // Go through boxlists
    $rows = db::query("SELECT * FROM internal_boxlists WHERE package = %s AND alias = %s ORDER BY id", $package, $alias);
    foreach ($rows as $row) { 

        // Place item
        if (row['position'] == 'top') { 
            array_unshift($hrefs, $row['href']);
        } elseif (preg_match("/^(before|after)\s(.+)/", $row['position'], $match)) { 

            if ($key = array_search($match[2], $hrefs)) { 
                if ($match[1] == 'after') { $key++; }
                array_splice($menus, $key, 0, $row['href']);
            } else { 
                $recheck[$row['href']] = $row['position'];
            }

        } else { 
            $hrefs[] = $row['href'];
        }
    }

    // Recheck leftover items
    foreach ($recheck as $href => $position) { 
        list($direction, $subling) = explode(' ', $position, 2);
        if ($key = array_search($sibling, $hrefs)) { 
            if ($direction == 'after') { $key++; }
            array_splice($hrefs, $key, 0, $href);
        } else { 
            $hrefs[] = $href;
        }
    }

    // Update ordering of items
    $order_num = 1;
    foreach ($hrefs as $href) { 
        db::query("UPDATE internal_boxlists SET order_num = %i WHERE package = %s AND alias = %s AND href = %s", $order_num, $package, $alias, $href);
        $order_num++;
    }

}

}

