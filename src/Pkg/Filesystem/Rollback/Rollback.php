<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Rollback;

use Apex\App\Cli\Cli;
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Svn\SvnFileConverter;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Utils\Io;
use Apex\Migrations\Handlers\Remover;

/**
 * Rollback
 */
class Rollback
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $file_converter;

    #[Inject(Remover::class)]
    private Remover $migrations;

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Rollback
     */
    public function process(string $pkg_alias, string $version):void
    {

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            return;
        }

        // Ensure upgrade dir exists
        $rollback_dir = SITE_PATH . '/.apex/upgrades/' . $pkg_alias . '/' . $version;
        if (!file_exists("$rollback_dir/config.json")) { 
            return;
        }
        $conf = json_decode(file_get_contents("$rollback_dir/config.json"), true);

        // Rollback migrations
        $this->rollbackMigrations($pkg_alias, $version, $conf);

        // Rollback files
        $this->rollbackFiles($rollback_dir, $conf, $pkg);
        $this->cli->send("Finalizing rollback of package $pkg_alias v$version... ");

        // Update configuration
        $this->pkg_config->install($pkg_alias);

        // Save package to new version
        $pkg->setVersion($conf['from_version']);
        $this->pkg_store->save($pkg);

        // Remove rollback dir
        $this->io->removeDir($rollback_dir);

        // Return
        $this->cli->send("done.\r\n\r\n");
    }

    /**
     * Rollback migrations
     */
    private function rollbackMigrations(string $pkg_alias, string $version, array $conf):void
    {

        // Message
        $this->cli->send("Rolling back migrations on package $pkg_alias v$version... ");

        // Go through migrations
        foreach ($conf['migrations'] as $class_name) {
            $this->migrations->removeMigration($pkg_alias, $class_name);
        }
        $this->cli->send("done.\r\n");

    }

    /**
     * Rollback files
     */
    private function rollbackFiles(string $rollback_dir, array $conf, LocalPackage $pkg):void
    {

        // Message
        $pkg_name = $pkg->getAlias() . ' v' . $pkg->getVersion();
        $this->cli->send("Rolling back files on package $pkg_name... ");

        // Get files
        $files = $this->io->parseDir($rollback_dir);


        // Go through files
        foreach ($files as $file) { 

            // Skip if config.json
            if ($file == 'config.json') { 
                continue;
            }

            // Convert file
            $local_file = $this->file_converter->toLocal($pkg, $file);
            $local_file = SITE_PATH . '/' . $local_file;

            // Delete existing file, if exists
            if (file_exists($local_file)) { 
                unlink($local_file);
            }

            // Rename
            $this->io->rename("$rollback_dir/$file", $local_file);
        }

        // Delete needed files
        foreach ($conf['files_added'] as $file) { 

            // Convert file
            $local_file = $this->file_converter->toLocal($pkg, $file);
            $local_file = SITE_PATH . '/' . $local_file;

            // Delete file if exists
            if (file_exists($local_file)) { 
                $this->io->removeFile($local_file, true);
            }
        }
        $this->cli->send("done.\r\n");
    }

}


