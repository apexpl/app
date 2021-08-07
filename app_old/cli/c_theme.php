<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, debug, io};
use apex\app\cli\cli;
use apex\app\pkg\theme;
use apex\app\sys\network\network;
use apex\app\exceptions\{ApexException, ThemeException};


/**
 * Handles all theme commands for the Apex CLI client.
 */
class c_theme
{

/**
 * List available themes.
 */
public function list(array $vars, network $client):string
{ 

    // Get themes
    $themes = $client->list_packages('theme');
    if (count($themes) == 0) { 
        return "No themes are available on any repositories.\n";
    }

    // Set blank array
    $available = [
        'public' => '',  
        'members' => ''
    ];

    // Go through themes
    foreach ($themes as $alias => $vars) { 
        $available[$vars['area']] .= $alias . ' -- ' . $vars['name'] . ' (' . $vars['author_name'] . ' <' . $vars['author_email'] . ">\n";
    }

    // Get response
    $response = '';
    if ($available['public'] != '') { 
        $response .= "--- Public Site Themes ---\n";
        $response .= "$available[public]\n";
    }
    if ($available['members'] != '') { 
        $response .= "--- Member Area Themes ---\n";
        $response .= "$available[members]\n";
    }
    debug::add(4, "CLI: list_themes", 'info');

    // Return
    return $response;

}

/**
 * Create theme
 */
public function create(array $vars)
{ 

    // Set variables
    $alias = strtolower($vars[0]) ?? '';
    $area = $vars[1] ?? 'public';
    $repo_id = $vars[2] ?? 0;
    debug::add(4, tr("CLI: Start theme creation, alias: {1}, area: {2}", $alias, $area), 'info');

    // Get repo ID
    if ($repo_id == 0) { 
        $repo_id = cli::get_repo();
    }

    // Create theme
    $theme = make(theme::class);
    $theme->create($alias, (int) $repo_id, $area);

    // Echo message
    $response = "Successfully created new theme, $alias.  New directories to implment the theme are now available at:\n\n";
    $response .= "\t/views/themes/$alias\n";
    $response .= "\t/public/themes/$alias\n\n";
    debug::add(4, tr("CLI: Completed theme creation, alias: {1}, area: {2}", $alias, $area), 'info');

    // Return
    return $response;

}

/*8
 * Initialize a theme.
 */
public function init(array $vars):string
{

    // Check theme
    $theme_alias = $vars[0] ?? '';
    if (!is_dir(SITE_PATH . '/views/themes/' . $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }
    debug::add(1, tr("Starting to initialize theme {1}", $theme_alias));

    // Set variables
    $client = make(theme::class);
    $theme_dir = SITE_PATH . '/views/themes/' . $theme_alias;
    $dirs = array('sections', 'tpl', 'layouts');

    // Go through dirs
    foreach ($dirs as $dir) { 
        if (!is_dir("$theme_dir/$dir")) { continue; }

        // Get files
        $files = io::parse_dir("$theme_dir/$dir");
        foreach ($files as $file) { 
            if (!preg_match("/\.tpl$/", $file)) { continue; }
            $client->init_file("$theme_dir/$dir/$file");
        }
    }

    // Debug
    debug::add(1, tr("Successfully initialized theme {1}", $theme_alias));

    // Return
    return "Successfully initialized the theme $theme_alias, and all files have been updated appropriately.";

}

/**
 * Delete theme
 */
public function delete(array $vars)"string
{ 

    // Set variables
    $theme_alias = $vars[0] ?? '';
    debug::add(4, tr("CLI: Start theme deletion: {1}", $theme_alias), 'info');

    // Delete theme
    $theme = make(theme::class);
    $theme->remove($theme_alias);
    debug::add(4, tr("CLI: Completed theme deletion: {1}", $theme_alias), 'info');

    // Return
    return "Successfully deleted the theme, $theme_alias\n";

}

/**
 * Publish theme
 */
public function publish(array $vars):string
{ 

    // Debug
    debug::add(4, tr("CLI: Start publishing theme: {1}", $vars[0]), 'info');

    // Upload theme
    $theme = make(theme::class);
    $theme->publish($vars[0]);
    debug::add(4, tr("CLI: Completed publishing theme: {1}", $vars[0]), 'info');

    // Give response
    return "Successfully published the theme, $vars[0]\n";

}

/**
 * Install theme
 */
public function install(array $vars):string
{ 

    // Set variables
    $theme_alias = $vars[0] ?? '';
    debug::add(4, tr("CLI: Start installing theme: {1}", $theme_alias), 'info');

    // Install theme
    $theme = make(theme::class);
    $theme->install($theme_alias);
    debug::add(4, tr("CLI: Completed installing theme: {1}", $theme_alias), 'info');

    // Return
    return "Successfully downloaded and installed the theme, $theme_alias\n";

}

/**
 * Change active theme on area.
 */
public function change(array $vars):string
{ 

    // Set variables
    $area = $vars[0] ?? '';
    $theme_alias = $vars[1] ?? '';

    // Perform checks
    if (!in_array($theme_alias, ['public','members','admin'])) { 
        throw new ApexException('error', "Invalid area specified, {1}", $area);
    } elseif (!$row = db::get_row("SELECT * FROM internal_themes WHERE alias = %s", $theme_alias)) { 
        throw new ThemeException('not_exists', $theme_alias);
    }

    // Update theme
    app::change_theme($area, $theme_alias);
    debug::add(4, tr("CLI: Changed theme on area '{1}' to theme: {2}", $area, $theme_alias), 'info');

    // Return
    return "Successfully changed the theme of area $area to the theme $theme_alias\n";

}











