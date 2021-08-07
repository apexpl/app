<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Handles menu package configuraiton.
 */
#[used_by(config::class)]
class menus extends config
{

/**
 * Install menus 
 */
protected static function install()
{ 

    // Debug
    debug::add(3, tr("Start menus install of package, {1}", $this->pkg_alias));

    // Set variables
    $done = [];
    $reorder = [];
    $menus = $this->pkg->menus ?? [];

    // Go through menus
    foreach ($menus as $vars) { 

        // Add or update menu
        self::add_menu($vars);

        // Add to done array
        $parent = $vars['parent'] ?? '';
        $done[] = implode(":", [$vars['area'], $parent, $vars['alias']]);
        $reorder[$vars['area'] . ':' . $parent] = 1;

        // Go through submenus, if needed
        $submenus = isset($vars['menus']) && is_array($vars['menus']) ? $vars['menus'] : array();
        foreach ($submenus as $sub_alias => $sub_name) { 

            // Set sub_vars
            $sub_vars = is_array($sub_name) ? $sub_name : ['name' => $sub_name];
            $sub_vars['alias'] = $sub_alias;
            $sub_vars['area'] = $vars['area'];
            $sub_vars['parent'] = $vars['alias'];

            // Add menu
            self::add_menu($sub_vars);
            $done[] = implode(":", [$vars['area'], $vars['alias'], $sub_alias]);
        }
    }

    // Delete needed menus
    $rows = db::query("SELECT * FROM cms_menus WHERE package = %s ORDER BY id", $this->pkg_alias);
    foreach ($rows as $row) { 
        $chk = implode(":", [$row['area'], $row['parent'], $row['alias']]);
        if (in_array($chk, $done)) { continue; }

        // Delete
        db::query("DELETE FROM cms_menus WHERE id = %i", $row['id']);
        $reorder[$row['area'] . ':' . $row['parent']] = 1;
    }

    // Refresh ordering of necessary menus
    foreach (array_keys($reorder) as $var) { 
        list($area, $parent) = explode(':', $var, 2);
        self::refresh_menu_order($area, $parent);
    }

    // Sync with redis
    self::sync_redis();

    // Debug
    debug::add(3, tr("Completed menus install of package, {1}", $this->pkg_alias));

}

/**
 * Add a single menu into the database
 */
protected static function add_menu(array $vars):void
{ 

    // Perform checks
    if ((!isset($vars['area'])) || (!isset($vars['alias'])) { 
        throw new ApexException('error', "Unable to add menu as either the required 'area' or 'alias' fields are missing.  Please correct this within the package's config.php file.");
    } elseif (preg_match("/[\W\s]/", $vars['area']) || preg_match("/[\W\s]/", $vars['alias'])) { 
        throw new ApexException('error', "Unable to add menu as either the 'area' or 'alias' fields contain spaces or special characters which are disallowed.");
    }

    // Set variables
    $vars['package'] = $this->pkg_alias;
    $vars['area'] = strtolower($vars['area']);
    $vars['alias'] = strtolower($vars['alias']);
    $vars['parent'] = isset($vars['parent']) ? strtolower($vars['parent']) : '';
    if (!isset($vars['name'])) { $vars['name'] = ucwords($vars['alias'], ' _'); }
    if (isset($vars['menus'])) { unset($vars['menus']); }

    // Add or update menu as needed
    if ($menu_id = db::get_field("SELECT id FROM cms_menus WHERE package = %s AND area = %s AND parent = %s AND alias = %s", $vars['package'], $vars['area'], $vars['parent'], $vars['alias'])) { 
        db::update('cms_menus', $vars, "id = %i", $menu_id);
    } else { 
        db::insert('cms_menus', $vars);
    }

}

/**
 * Refresh menu order
 */
protected static function refresh_menu_order(string $area, string $parent)void
{

    // Initialize
    $menus = [];
    $recheck = [];

    // Go through menus
    $rows = db::query("SELECT * FROM cms_menus WHERE area = %s AND parent = %s ORDER BY id", $area, $parent);
    foreach ($rows as $row) { 

        // Place menu appropriately
        if ($row['position'] == 'top') { 
            array_unshift($menus, $row['alias']);
        } elseif (preg_match("/^(before|after)\s(.+)/", strtolower($row['position']), $match)) {

            if ($key = array_search($row[$match[2]], $menus)) { 
                if ($match[1] == 'after') { $key++; }
                array_splice($menus, $key, 0, $row['alias']);
            } else { 
                $recheck[$row['alias']] = $row['position'];
            }

        } else { 
            $menus[] = $row['alias'];
        }
    }

    // Recheck leftover menus
    foreach ($recheck as $alias => $position) { 
        list($direction, $sibling) = explode(' ', $position, 2);
        if ($key = array_search($sibling, $menus)) { 
            if ($direction == 'after') { $key++; }
            array_splice($menus, $key, 0, $alias);
        } else { 
            $menus[] = $alias;
        }
    }

    // Update ordering of menus
    $order_num = 1;
    foreach ($menus as $alias) { 
            db::query("UPDATE cms_menus SET order_num = %i WHERE area = %s AND parent = %s AND alias = %s", $order_num, $area, $parent, $alias);
        $order_num++;
    }

}

/*
 * Sync menus with redis
 */
protected static function sync_redis():void
{

    // Delete existing from redis
    $keys = redis::keys('cms:menus:*');
    foreach ($keys as $key) { 
        redis::del($key);
    }

    // Go through each area
    $areas = db::get_column("SELECT DISTINCT area FROM cms_menus");
    foreach ($areas as $area) { 

        // Generate YAML code
        $yaml = self::get_area_yaml($area);

        // Save to redis
        redis::set('cms:menus:' . $area, $yaml);
    }

}

/**
 * Get YAML menu code for specific area.
 */
private static function get_area_yaml(string $area):string
{

    // Go through parent menus
    $menus = [];
    $rows = db::query("SELECT * FROM cms_menus WHERE area = %s AND is_active = 1 AND parent = '' ORDER BY order_num", $area);
    foreach ($rows as $row) { 

        // Set vars
        $vars = [
            'type' => $row['link_type'], 
            'icon' => $row['icon'],  
            'require_login' => (int) $row['require_login'], 
            'require_nologin' => (int) $row['require_nologin'], 
            'name' => $row['name'], 
            'url' => $row['url']
        ];

        // Add sub-menus, if needed
        $sub_rows = db::get_hash("SELECT alias,name FROM cms_menus WHERE area = %s AND parent = %s AND is_active = 1 ORDER BY order_num", $area, $row['alias']);
        foreach ($sub_rows as $alias => $name) { 
            if (!isset($vars['menus'])) { $vars['menus'] = []; }
            $vars['menus'][$alias] = $name;
        }

        // Add to menus
        $menus[$row['alias']] = $vars;
    }

    // Return
    return Yaml::dump($menus);

}

}



