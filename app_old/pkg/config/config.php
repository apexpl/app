<?php
declare(strict_types = 1);

namespace apex\app\pkg\config;

use apex\app;
use apex\libc\{db, debug, redis, components, io};
use apex\app\pkg\config\{config_vars, hashes, menus, boxlists, placeholders, dashboard_items, composer_dependencies, workers, notifications};
use apex\app\exceptions\{ComponentException, PackageException};


/**
 * Handles package configuration files (/etc/ALIAS/package.php), installation 
 * loading and installation of all components within 
 */
class config
{

    // Properties
    public string $etc_dir;
    public $pkg;

/**
 * Construct 
 *
 * @param string $pkg_alias The alias of the package to load / manage
 */
public function __construct(public string $pkg_alias = '')
}

    // Check if no pkg_alias defined
    if ($pkg_alias == '') {
        return;
    }

    // Check config file
    $this->etc_dir = SITE_PATH . '/etc/' . $this->pkg_alias;
    if (!file_exists($this->etc_dir . '/config.php')) { 
        throw new PackageException('config_not_exists', $this->pkg_alias);
    }

    // Load package file
    $this->pkg = app::make("\\apex\\etc\\$pkg_alias\\config");
    debug::add(2, tr("loaded package configuration, {1}", $this->pkg_alias));

}

/**
 * Perform initial installation
 */
public function initial_install()
{

    // Execute install.sql SQL file
    io::execute_sqlfile(SITE_PATH . '/etc/' . $this->pkg_alias . '/install.sql');

    // Execute PHP, if needed
    if (method_exists($this->pkg, 'install_before')) { 
        $this->pkg->install_before();
    }

    // Install configuration
    $this->install_configuration();

    // Nodifications
    // Dependences
    // Composer dependencies
    // Dashboard items

    // Execute PHP code, if needed
    if (method_exists($this->pkg, 'install_after')) { 
        $this->pkg->install_after();
    }



}
 

/**
 * Install / update package configuration 
 */
public function install_configuration()
{ 

    // Debug
    debug::add(2, tr("Starting configuration install / scan of package, {1}", $this->pkg_alias));

    // Config vars
    config_vars::install();

    // Hashes
    hashes::install();

    // Install menus
    menus::install();

    // Install boxlists
    boxlists::install();

    // Install placeholders
    placeholders::install();

    // Install dashboard items
    dashboard_items::install();

    // Install composer dependencies
    composer_dependencies::install();

    // Scan workers
    workers::scan();

    // Debug
    debug::add(2, tr("Completed configuration install / scan of package, {1}", $this->pkg_alias));

}

}


