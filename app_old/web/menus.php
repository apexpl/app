<?php
declare(strict_types = 1);

namespace apex\app\web;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\web\html_tags;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use apex\app\exceptions\ApexException;


/**
 * Manages all menus within Apex, and ensures the YAML code stored within 
 * redis is always synced to the menus within the SQL database.
 */
class menus
{

/**
 * Get HTML for nav menus.
 */
public static function get_html(html_tags $html_tags, string $area, string $selected = ''):string
{

    // Get needed tag html
    $tags = [
        'header' => $html_tags->get_tag('nav.header'), 
        'parent' => $html_tags->get_tag('nav.parent'), 
        'internal' => $html_tags->get_tag('nav.menu')
    ];

    // Get menus
    $menus = self::get($area);

    // Go through menus
    $html = '';
    foreach ($menus as $alias => $row) { 
        if (!self::check_viewable($row)) { continue; }

        // Get sub-menu html
        $sub_html = '';
        $sub_menus = $row['menus'] ?? [];
        foreach ($sub_menus as $sub_alias => $sub_name) { 
            $url = $area == 'public' ? "/$alias/$sub_alias" : "/$area/$alias/$sub_alias";
            $temp_sub = str_replace("~url~", $url, $tags['internal']);
            $temp_sub = str_replace('~icon~', '', $temp_sub);
            $sub_html .= str_replace('~name~', $sub_name, $temp_sub);
        }

        // Get uri link
        if ($row['type'] == 'parent') { 
            $url = '#';
        } elseif ($row['type'] == 'external') { 
            $url = $row['url'];
        } else { 
            $url = $area == 'public' ? "/$alias" : "/$area/$alias"; 
        }

        // Set variables
        $vars = [
            '~url~' => $url, 
            '~icon~' => $row['icon'] == '' ? '' : '<i class="' . $row['icon'] . '"></i>', 
            '~name~' => $row['name'], 
            '~submenus~' => $sub_html
        ];

        // Add to html
        $temp_html = $tags[$row['type']] ?? $tags['internal'];
        $html .= strtr($temp_html, $vars);
    }

    // Return
    return $html;

}


}

