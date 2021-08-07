<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, debug};
use apex\app\pkg\config\config;
use apex\app\attributes\used_by;
use apex\app\exceptions\ApexException;


/**
 * Handles dashboard items package configuration.
 */
#[used_by(config::class)]
class dashboard_items extends config
{


/**
 * Install dashboard items
 */
protected static function install():void
{

    // Initialize
    $done = [];
    $items = $this->pkg->dashboard_items ?? [];

    // Add dashboard items
    foreach ($dashboard_items as $vars) { 

        // Perform checks
        if ((!isset($vars['type'])) || (!isset($vars['alias'])) || (!isset($vars['area']))) { 
            throw new ApexException('error', "Unable to add dashboard item as it does not contain one of the required fields: 'type', 'area', 'alias'");
        } elseif (!in_array($vars['type'], ['top','right','tab'])) { 
            throw new ApexException('error', "Unable to add dashboard item as it has an invalid 'type' variable.  Valid types are: top, right, tab");
        }

        // Set variables
        $done[] = $vars['type'] . '_' . $vars['alias'];
        $divid = $vars['divid'] ?? '';
        $panel_class = $vars['panel_class'] ?? '';

        // Update, if already exists
        if ($row = db::get_row("SELECT * FROM dashboard_items WHERE package = %s AND alias = %s AND type = %s AND area = %s", $this->pkg_alias, $vars['alias'], $vars['type'], $vars['area'])) { 

            // Update database
            db::update('dashboard_items', array(
                'area' => $vars['area'], 
                'divid' => $divid,
                'panel_class' => $panel_class,  
                'title' => $vars['title'], 
                'description' => $vars['description']), 
            "id = %i", $row['id']);
            continue;

        }

        // Add new item
        db::insert('dashboard_items', array(
            'package' => $this->pkg_alias, 
            'area' => $vars['area'], 
            'type' => $vars['type'], 
            'divid' => $divid, 
            'panel_class' => $panel_class, 
            'alias' => $vars['alias'], 
            'title' => $vars['title'], 
            'description' => $vars['description'])
        );

    }

    // Delete needed dashboard items
    $rows = db::query("SELECT * FROM dashboard_items WHERE package = %s", $this->pkg_alias);
    foreach ($rows as $row) { 
        $alias = $row['type'] . '_' . $row['alias'];
        if (in_array($alias, $done)) { continue; }
        db::query("DELETE FROM dashboard_items WHERE id = %i", $row['id']);
    }

}

/**
 * Install default dashboard items
 */
public static function install_default():void
{

    // Go through items
    $dashboard_items = $this->pkg->dashboard_items ?? [];
    foreach ($dashboard_items as $vars) { 
        if (!isset($vars['is_default'])) { continue; }
        if ($vars['is_default'] != 1) { continue; }

        // Get profile ID
        if (!$profile_id = db::get_field("SELECT id FROM dashboard_profiles WHERE area = %s AND is_default = 1", $vars['area'])) { 
            continue;
        }

        // Delete core items, if they exist
        db::query("DELETE FROM dashboard_profiles_items WHERE profile_id = %i AND type = %s AND package = 'core'", $profile_id, $vars['type']);

        // Add to database
        db::insert('dashboard_profiles_items', array(
            'profile_id' => $profile_id, 
            'type' => $vars['type'], 
            'package' => $this->pkg_alias, 
            'alias' => $vars['alias'])
        );
    }

}

}


