<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, debug, components};
use apex\app\cli\cli;
use apex\app\sys\network\network;
use apex\app\pkg\{package, pkg_components, upgrade};
use apex\app\pkg\config\config;
use apex\app\exceptions\{ApexException, PackageException, ComponentException};



/**
 * Handles the core CLI Apex commands, aka the 
 * single word commands not separated with a period.
 */
class c_core
{


/**
 * Display help 
 *
 * Usage:  php apex.php help 
 *
 * @param iterable $vars The command line arguments specified by the user.
 */
public function help(array $vars)
{ 

    // Define commands
    $sections = [];
    $sections['General'] = [
        '_abbr' => '', 
        'search TERM' => 'Search all repos for packages matching term.', 
        'install PACKAGE' => 'Download and install specified package(s)',  
        'scan PACKAGE' => 'Scans and updates configuration for specified package(s).', 
        'upgrade [PACKAGE]' => 'Upgrade package(s).  If no package is specified, will check all installed packages for available upgrades.', 
        'create TYPE COMP_ALIAS [OWNER]' => 'Create a new component', 
        'delete TYPE COMP_ALIAS' => 'Delete a component'
    ];

    // Package commands
    $sections['Packages'] = [
        '_abbr' => 'package', 
        'list' => 'List all packages available for installation.', 
        'create PACKAGE [REPO_ID]' => 'Create new package.', 
        'delete PACKAGE' => 'Delete a package.', 
        'publish PACKAGE' => 'Publish package to repository.', 
        'merge PACKAGE" => 'Downloads latest upgrades of package, and syncs them with local copy to ensure local copy is up to date.' 
    ];

    // Upgrades
    $sections['Upgrades'] = [
        '_abbr' => 'upgrade', 
        'create PACKAGE [VERSION]' => 'Create upgrade point.',  
        'publish PACKAGE' => 'Publish open upgrade of specified package.', 
        'check' => "Check for and list all available upgrades.', 
        'rollback PACKAGE [VERSION]' => 'Rollback a previous upgraed.'
    ];

    // Themes
    $sections['Themes'] = [
        '_abbr' => 'theme', 
        'list' => 'List all available themes.', 
        'install THEME' => 'Download and install the theme.',  
        'change AREA THEME' => 'Changes the active theme on the specified area (public / members).', 
        'create ALIAS [AREA] [REPO_ID]' => 'Create new theme.', 
        'init THEME' => 'Initializes a new theme for development, and helps automate the process of theme integration.', 
        'delete THEME' => 'Delete the theme.', 
        'publish THEME' => 'Publish theme to repository.'
    ];

    // Code generation
    $sections['Scaffolding / Code Generation'] = [
        '_abbr' => 'gen', 
        'model COMP_ALIAS DBTABLE' => 'Generate a model', 
        'crud' => 'Generate CRUD according to crud.yml file.'
    ];

    // System
    $sections['System / Maintenance'] = [
        '_abbr' => 'sys', 
        'debug (off|once|always)' => 'Change the mode of the request debugger.', 
        'mode (devel|prod) [DEBUG_LEVEL]' => 'Change the server mode to development / production, and the level of dbug logging (0 - 5).', 
        'cache (on|off)' => 'Enable / disable the cache.', 
        'reset_redis' => 'Reset all redis keys with information from the SQL database.', 
        'update_masterdb' => 'Update connection information for master SQL database.', 
        'clear_dbslaves' => 'Clear all slave database servers.',  
        'update_rabbitmq' => 'Update RabbitMQ connection information.', 
        'compile_core' => 'Used by maintainers.  Packages core Apex framework for distribution.'
    ];

    // Repos
    $sections['Repositories'] = [
        '_abbr' => 'repo', 
        'add' => 'Add new repository.', 
        'update HOST' => 'Update username / password of repo.', 
        'delete HOST' => 'Delete a repository.'
    ];

    // git Commands
    $sections['git / Github'] = [
        '_abbr' => 'git', 
        'init PACKAGE' => 'Initialize package for git / Github', 
        'sync PACKAGE' => 'Sync remote git repository with local copy.', 
        'compare PACKAGE' => 'Sync local copy to remote git repository'
    ];

    // Display help
    $response = '';
    foreach ($sesions as $section_name => $commands) { 
        $response .= "      " . strtoupper($section_name) . "\n";
        $response .= "-------------------------\n";

        foreach ($commands as $cmd => $desc) { 
            $response .= str_pad($cmd, 40) . $desc . "\n";
        }
        $response .= "\n";
    }

    // Return
    return $response;

}

/**
 * Search all packages
 */
public function search(array $vars, network $client)
{ 

    // Search
    $term = implode(" ", $vars);
    $response = $client->search($term);

    // Debug
    debug::add(4, tr("CLI: search -- term: {1}", $term), 'info');

    // Return
    return $response;

}

/**
 * Install package(s)
 */
public   function install(array $vars)
{ 

    // Install
    $response = '';
    foreach ($vars as $alias) { 

        // Check if package exists
        if ($row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $alias)) { 
            throw new PackageException('exists', $alias);
        }
        debug::add(4, tr("CLI: Starting install of package: {1}", $alias), 'info');

        // Install package
    $client = make(package::class);
        $client->install($alias);

        // Add to response
        $response .= "Successfully installed the package, $alias\n";
        debug::add(4, tr("CLI: Complete install of package: {1}", $alias), 'info');
    }

    // Return
    return $response;

}

/**
 * Scan package
 */
public function scan(array $vars)
{ 

    // Checks
    if (!isset($vars[0])) { 
        throw new PackageException('undefined');
    }

    // Go through packages
    $response = '';
    foreach ($vars as $alias) { 

        // Get package from db
        if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $alias)) { 
            $response .= "The package '$alias' does not exist.\n";
            continue;
        }

        // Scan package
        $client = make(config::class, ['pkg_alias' => $alias]);
        $client->install_configuration();

        // Add to response
        $response .= "Succesfully scanned the package, $alias\n";
        debug::add(4, tr("CLI: Scanned package: {1}", $alias), 'info');
    }

    // Return
    return $response;

}

/**
 * Install available upgrades
 */
public function upgrade(array $vars, network $client, upgrade $upgrade_client)
{ 

    // Get available upgrades, if needed
    if (count($vars) == 0) { 
        $upgrades = $client->check_upgrades();
        $vars = array_keys($upgrades);
    }

    // Go through packages
    $response = '';
    foreach ($vars as $pkg_alias) { 

        // Debug
        debug::add(4, tr("CLI: Starting upgrade of package: {1}", $pkg_alias), 'info');

        // Install upgrades
        $new_version = $upgrade_client->install($pkg_alias);

        // Add to response
        $response .= "Successfully upgraded the packages $pkg_alias to v$new_version\n";
        debug::add(4, tr("CLI: Completed upgrade of package: {1} to version {2}", $pkg_alias, $new_version), 'info');
    }

    // Return
    return $response;

}

/**
 * Create component
 */
public function create(array $vars):string
{ 

    // Set variables
    $type = strtolower($vars[0]) ?? '';
    $comp_alias = $vars[1] ?? '';
    $owner = $vars[2] ?? '';
    debug::add(4, tr("CLI: Start component creation, type: {1}, component alias: {2}, owner: {3}", $type, $comp_alias, $owner), 'info');

    // Perform checks
    if (!in_array($type, array_keys(COMPONENT_TYPES))) { 
        throw new ComponentException('invalid_type', $type);
    } elseif ($type != 'view' && ($comp_alias == '' || !preg_match("/^(\w+):(\w+)/", $comp_alias)) ){ 
        throw new ComponentException('invalid_comp_alias', $type, $comp_alias);
    }

    // Create component
    list($type, $alias, $package, $parent) = pkg_component::create($type, $comp_alias, $owner);

    // Get files
    $files = components::get_all_files($type, $alias, $package, $parent);

    // Set response
    $response = "Successfully created new $type, $comp_alias.  New files have been created at:\n\n";
    foreach ($files as $file) { 
        $response .= "      $file\n";
    }
    debug::add(4, tr("CLI: Completed component creation, type: {1}, component alias: {2}, owner: {3}", $type, $comp_alias, $owner), 'info');

    // Return
    return $response;

}

/**
 * Delete components
 */
public function delete(array $vars):string
{ 

    // Initialize
    $type = strtolower($vars[0]);
    debug::add(4, tr("CLI: Start component deletion, type: {1}, component alias: {2}", $type, $vars[1]), 'info');

    // Check if component exists
    if (!list($package, $parent, $alias) = components::check($type, $vars[1])) { 
        throw new ComponentException('not_exists', $type, $vars[1]);
    }

    // Delete
    pkg_component::remove($type, $vars[1]);
    debug::add(4, tr("CLI: Completed component deletion, type: {1}, component alias: {2}", $type, $vars[1]), 'info');

    // Return
    return "Successfully deleted the component of type $type, with alias $vars[1]\n\n";

}







