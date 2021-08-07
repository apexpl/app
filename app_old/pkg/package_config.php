<?php
declare(strict_types = 1);

namespace apex\app\pkg;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\redis;
use apex\libc\components;
use apex\app\exceptions\ComponentException;
use apex\app\exceptions\PackageException;
use apex\app\pkg\pkg_component;
use apex\core\notification;


/**
 * Handles package configuration files (/etc/ALIAS/package.php), installation 
 * loading and installation of all components within 
 */
class package_config
{



    // Properties
    public $pkg_alias;
    public $pkg_dir;

/**
 * Construct 
 *
 * @param string $pkg_alias The alias of the package to load / manage
 */
public function __construct(string $pkg_alias = '')
{ 

    // Set variables
    $this->pkg_alias = $pkg_alias;
    $this->pkg_dir = SITE_PATH . '/etc/' . $pkg_alias;

    // Debug
    debug::add(3, tr("Initialized package, {1}", $pkg_alias));

}

/**
 * Load package configuration 
 *
 * Loads a package configuration file, and ensures any omitted arrays within 
 * package configuration are set as blank arrays to avoid errors in the rest 
 * of the class. 
 */
public function load()
{ 

    // Ensure package.php file exists
    if (!file_exists($this->pkg_dir . '/package.php')) { 
        throw new PackageException('config_not_exists', $this->pkg_alias);
    }

    // Load package file
    require_once($this->pkg_dir . '/package.php');
    $class_name = "\\apex\\pkg_" . $this->pkg_alias;

    // Initiate package class
    $pkg = new $class_name();

    // Blank out needed arrays
    $vars = array(
        'config',
        'hash',
        'menus',
        'ext_files',
        'boxlists',
        'placeholders',
        'notifications', 
        'dashboard_items', 
        'dependencies', 
        'composer_dependencies'
    );

    foreach ($vars as $var) { 
        if (!isset($pkg->$var)) { $pkg->$var = array(); }
    }

    // Debug
    debug::add(2, tr("loaded package configuration, {1}", $this->pkg_alias));

    // Return
    return $pkg;

}

/**
 * Install / update package configuration 
 *
 * Goes through the package.php configuration file, and updates the database 
 * as necessary.  Ensures to update existing records as necessary, and delete 
 * records that have been removed from the package.php file. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
public function install_configuration($pkg = '')
{ 

    // Debug
    debug::add(2, tr("Starting configuration install / scan of package, {1}", $this->pkg_alias));

    // Load package, if needed
    if (!is_object($pkg)) { 
        $pkg = $this->load();
    }

    // Config vars
    $this->install_config_vars($pkg);

    // Hashes
    $this->install_hashes($pkg);

    // Install menus
    $this->install_menus($pkg);

    // Install boxlists
    $this->install_boxlists($pkg);

    // Install placeholders
    $this->install_placeholders($pkg);

    // Install dashboard items
    $this->install_dashboard_items($pkg);

    // Install composer dependencies
    $this->install_composer_dependencies($pkg);

    // Scan workers
    $this->scan_workers();

    // Debug
    debug::add(2, tr("Completed configuration install / scan of package, {1}", $this->pkg_alias));

}

/**
 * Install configuration variables 
 *
 * Adds / updates the configuration variables as necessary from the 
 * package.php $this->config() array. 
 *
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_config_vars($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting install of config vars for package, {1}", $this->pkg_alias));

    // Add config vars
    foreach ($pkg->config as $alias => $value) { 
        $comp_alias = $this->pkg_alias . ':' . $alias;
        pkg_component::add('config', $comp_alias, (string) $value);

        // Add to redis
        if (!redis::hexists('config', $comp_alias)) { 
            redis::hset('config', $comp_alias, $value);
        }
    }

    // Check for deletions
    $chk_config = db::get_column("SELECT alias FROM internal_components WHERE package = %s AND type = 'config'", $this->pkg_alias);
    foreach ($chk_config as $chk) { 
        if (in_array($chk, array_keys($pkg->config))) { continue; }
        $comp_alias = $this->pkg_alias . ':' . $chk;
        pkg_component::remove('config', $comp_alias);
        redis::hdel('config', $comp_alias);
    }

    // Debug
    debug::add(3, tr("Completed install of configuration variables for package, {1}", $this->pkg_alias));

}

/**
 * Install hashes 
 *
 * Adds / updates / deletes the hashes within the package.php file's 
 * $this->hash array.  Hashes are used as key-value paris to easily populate 
 * select / radio / checkbox lists. 
 * 
 * @param mixed $pkg The loaded package configuration.
 */
protected function install_hashes($pkg)
{ 

    // Debug
    debug::add(3, tr("Starting hashes install of package, {1}", $this->pkg_alias));

    // Add needed hashes
    foreach ($pkg->hash as $hash_alias => $vars) { 
        if (!is_array($vars)) { continue; }

        // Add / update hash
        $comp_alias = $this->pkg_alias . ':' . $hash_alias;
        redis::hset('hash', $comp_alias, json_encode($vars));
        pkg_component::add('hash', $comp_alias);

        // Check for var deletion
        $chk_vars = db::get_column("SELECT alias FROM internal_components WHERE type = 'hash_var' AND package = %s AND parent = %s", $this->pkg_alias, $hash_alias);
        foreach ($chk_vars as $chk) { 
            if (in_array($chk, array_keys($vars))) { continue; }
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
        if (in_array($chk, array_keys($pkg->hash))) { continue; }
        pkg_component::remove('hash', $this->pkg_alias . ':' . $chk);
        redis::hdel('hash', $this->pkg_alias . ':' . $chk);
    }

    // Debug
    debug::add(3, tr("Completed hashes install of package, {1}", $this->pkg_alias));

}


