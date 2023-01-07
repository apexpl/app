<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\Svc\{Container, Db};
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\App\Network\Models\LocalPackage;
use Apex\Migrations\Handlers\Installer as MigrationInstaller;
use Apex\App\Attr\Inject;

/**
 * Initial install / remove migrations
 */
class Migration
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    #[Inject(MigrationInstaller::class)]
    private MigrationInstaller $migration_installer;

    /**
     * Install
     */
    public function install(LocalPackage $pkg):void
    {

        // Run migrations, if they exist
        if (null !== ($obj = $this->load($pkg))) {
 
            // Pre-install
            if (method_exists($obj, 'preInstall')) { 
                $obj->preInstall($this->db);
            }

        // Install
            $obj->install($this->db);
        }

        // Install configuration
        $this->pkg_config->install($pkg->getAlias());

        // Run post-install
        if (is_object($obj) && method_exists($obj, 'postInstall')) { 
            $obj->postInstall($this->db);
        }

        // Install additional migrations
        $this->migration_installer->setSendOutput(false);
        $this->migration_installer->migratePackage($pkg->getAlias(), true);

    }

    /**
     * Remove
     */
    public function remove(LocalPackage $pkg):void
    {

        // Run migrations, if they exist
        if (null !== ($obj = $this->load($pkg))) {

            // Pre-remove
            if (method_exists($obj, 'preRemove')) { 
                $obj->preRemove($this->db);
            }

            // Remove
            $obj->remove($this->db);
        }

        // Remove configuration
        $this->pkg_config->remove($pkg->getAlias());

        // Run post-remove
        if (is_object($obj) && method_exists($obj, 'postRemove')) { 
            $obj->postRemove($this->db);
        }

    }

    /**
     * Load
    */
    private function load(LocalPackage $pkg):?object
    {

        // Load migrate.php file
        $migrate_file = SITE_PATH . '/etc/' . $pkg->getAliasTitle() . '/migrate.php';
        if (!file_exists($migrate_file)) { 
            return null;
        }
        require_once($migrate_file);

        // Load migrations
        $class_name = "\\Etc\\" . $pkg->getAliasTitle() . "\\migrate";
        if (!class_exists($class_name)) { 
            return null;
        }

        // Load class
        $obj = $this->cntr->make($class_name);
        return $obj;
    }

}


