<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\App;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Cli\Cli;
use Apex\App\Network\Svn\{SvnChangelog, SvnFileConverter};
use Apex\App\Network\Sign\{MerkleTreeBuilder, VerifyDownload};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\MerkleTree;
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\App\Pkg\Filesystem\Package\Inventory;
use Apex\App\Pkg\Filesystem\Rollback\Compiler;
use Apex\App\Sys\Utils\Io;
use Apex\Migrations\Handlers\Installer;
use Apex\App\Attr\Inject;

/**
 * Svn Upgrade
 */
class SvnUpgrade
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(SvnChangelog::class)]
    private SvnChangelog $svn_changelog;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Inventory::class)]
    private Inventory $pkg_inventory;

    #[Inject(MerkleTreeBuilder::class)]
    private MerkleTreeBuilder $tree_builder;

    #[Inject(VerifyDownload::class)]
    private VerifyDownload $verify_download;

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $file_converter;

    #[Inject(Compiler::class)]
    private Compiler $rollback;

    #[Inject(Installer::class)]
    private Installer $migrations;

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Process upgrade
     */
    public function process(LocalPackage $pkg, bool $confirm = false, bool $noverify = false):?string
    {

        // Initialize
        $svn = $pkg->getSvnRepo();
        $pkg_serial = $pkg->getSerial();

        // check for upgrades
        if (!$releases = $this->checkUpgrades($pkg)) { 
            return null;
        }

        // Check for breaking changes
        if ($confirm !== true && true !== $this->checkBreakingChanges($pkg, $releases)) { 
            return null;
        }
        $latest_version = array_pop($releases);

        // Export inventory
        if (!list($tmp_dir, $inventory) = $this->exportInventory($pkg, $latest_version)) { 
            return null;
        }

        // Verify
        //if ($noverify === true) { 
            //$this->cli->send("Skipping verification checks...\r\n");
        //} elseif (!$this->verify($pkg, $inventory, $tmp_dir, $latest_version)) { 
            //return null;
        //}

        // Install
        $this->install($pkg, $inventory, $tmp_dir, $latest_version);

        // Finalize
        $this->finalize($pkg, $latest_version, $tmp_dir);
        return $latest_version;
    }

    /**
     * Check for upgrades
     */
    private function checkUpgrades(LocalPackage $pkg):?array
    {

        // Initialize
        $releases = [];
        $current_version = $pkg->getVersion();
        $this->cli->send("Checking package " . $pkg->getSerial() . " for upgrades... ");

        // Get all releases
        if (!$svn_releases = $pkg->getSvnRepo()->getReleases()) { 
            $this->cli->send("none found.\r\n");
            return null;
        }

        // Get available versions
    foreach ($svn_releases as $version) { 
            if (version_compare($current_version, $version, '<')) { 
                $releases[] = $version;
            }
        }

        // Check for releases
        if (count($releases) == 0) { 
            $this->cli->send("none found.\r\n");
            return null;
        } else { 
            $this->cli->send("found " . count($releases) . " upgrades.\r\n");
        }

        // Return
        return $releases;
    }

    /**
     * Check for breaking changes
     */
    private function checkBreakingChanges(LocalPackage $pkg, array $releases):bool
    {

        // Initialize
        $has_breaking = false;
        $svn = $pkg->getSvnRepo();
        $this->cli->send("Checking for breaking changes... ");

        // Go through upgrades
        foreach ($releases as $version) { 

            // Get property
            if (!$is_breaking = $svn->getProperty('is_breaking', 'tags/' . $version)) { 
                continue;
            } elseif ($is_breaking == 1) { 
                $has_breaking = true;
                break;
            }
        }

        // Confirm install, if needed
        if ($has_breaking === true) { 
            $this->cli->send("found.\r\n\r\n");
            if (true !== $this->cli->getConfirm("One or more of the upgrades for the package " . $pkg->getSerial() . " have been marked to contain breaking changes.  Did you want to continue installing the upgrades?", 'y')) { 
                return false;
            }
        } else { 
            $this->cli->send("none found.\r\n");
        }

        // Return
        return true;
    }

    /**
     * Export inventory
     */
    private function exportInventory(LocalPackage $pkg, string $latest_version):?array
    {

        // Initialize
        $this->cli->send("Exporting inventory for package " . $pkg->getSerial() . "... ");
        $tag_dir = 'tags/' . $latest_version;
        $svn = $pkg->getSvnRepo();
        $is_ssh = false;

        // Get inventory
        if (!$inventory = $this->svn_changelog->get($pkg->getSvnRepo(), $pkg->getVersion())) { 
            return null;
        }
        $this->cli->send(count($inventory['updated']) . ' files found... ');

        // Create tmp dir
        $tmp_dir = sys_get_temp_dir() . '/apex-' . uniqid(); 
        $this->io->createBlankDir($tmp_dir);

        // Download exported files
        foreach ($inventory['updated'] as $file) { 

            // Create parent dir, if needed
            $local_file = $tmp_dir . '/' . $file;
            if (!is_dir(dirname($local_file))) { 
                mkdir(dirname($local_file), 0755, true);
            }

            // Export
            $svn->setTarget($tag_dir . '/' . $file, 0, false, $is_ssh);
            if (!$svn->exec(['export'], [$local_file])) { 
                $is_ssh = true;
                $svn->setTarget($tag_dir . '/' . $file, 0, false, true);
                if (!$svn->exec(['export'], [$local_file])) { 
                    $this->cli->error("Unable to export file $file from repository.  Error received is: " . $svn->error_output);
                    return null;
                }
            }

        }

        // Return
        $this->cli->send("done.\r\n");
        return [$tmp_dir, $inventory];
    }

    /**
     * Verify upgrade
     */
    private function verify(LocalPackage $pkg, array $upgrade_inventory, string $tmp_dir, string $latest_version):?string
    {

        // Build merkle tree
        $tree = $this->buildMerkleTree($pkg, $upgrade_inventory, $tmp_dir, $latest_version);
        $this->cli->send("Verifying digital signature... ");

        // Verify
        if (!$signed_by = $this->verify_download->verify($pkg->getSvnRepo(), 'tags/' . $latest_version, $tmp_dir, $tree)) { 
            $this->cli->send("verification failed, unable to install.  Use --noverify flag to override.\r\n");
            return null;
        }
        $this->cli->send("done (signed by: $signed_by).\r\n");

        // Return
        return $signed_by;
    }

    /**
     * Build merkle tree
     */
    private function buildMerkleTree(LocalPackage $pkg, array $upgrade_inventory, string $tmp_dir, string $latest_version):MerkleTree
    {

        // Get inventory
        $this->cli->send("Building merkle root... ");
        $inventory = $this->pkg_inventory->get($pkg);

        // Add upgrade inventory
    foreach ($upgrade_inventory['updated'] as $file) { 
            $inventory[$file] = sha1_file($tmp_dir . '/' . $file);
        }

        // Delete from inventory
        foreach ($upgrade_inventory['deleted'] as $file) { 
            unset($inventory[$file]);
        }

        // Get prev merkle root
        $svn = $pkg->getSvnRepo();
        $prev_merkle_root = $svn->getProperty('prev_merkle_root', 'tags/' . $latest_version);

        // Build merkle tree
        $tree = $this->tree_builder->build($pkg, $inventory, $prev_merkle_root);

        // Return
        $this->cli->send("done.\r\n");
        return $tree;
    }

    /** 
     * Install upgrade
     */
    private function install(LocalPackage $pkg, array $inventory, string $tmp_dir, string $latest_version)
    {

        // Initialize
        $this->cli->send("Installing upgrade... ");
        $this->rollback->initialize($pkg, $latest_version);

        // Copy over files
        foreach ($inventory['updated'] as $file) { 

            // Convert file
            $local_file = $this->file_converter->toLocal($pkg, $file);
            $local_file = SITE_PATH . '/' . $local_file;

            // Create parent directory, if needed
            if (!is_dir(dirname($local_file))) { 
                mkdir(dirname($local_file), 0755, true);
            }

            // Add file to rollback
            $this->rollback->addFile($pkg, $local_file, $file, $latest_version);

            // Rename file
            $this->io->rename($tmp_dir . '/' . $file, $local_file);
        }

        // Delete needed files
        foreach ($inventory['deleted'] as $file) { 

            // Convert file
            $local_file = $this->file_converter->toLocal($pkg, $file);
            $local_file = SITE_PATH . '/' . $local_file;

            // Skip, if local file not exists
            if (!file_exists($local_file)) { 
                continue;
            }

            // Add to rollback
            $this->rollback->addFile($pkg, $local_file, $file, $latest_version);
        }

        // Return
        $this->cli->send("done.\r\n");
    }

    /**
     * Finalize
     */
    public function finalize(LocalPackage $pkg, string $latest_version, string $tmp_dir)
    {

        // Perform migrations
        $installed = [];
        if ($this->app->isSlave() !== true) {
            $this->cli->send("Performing migrations... ");
        $installed = $this->migrations->migratePackage($pkg->getAlias()) ?? [];
            $this->cli->send("done.\r\n");
        }
        $this->cli->send("Cleaning up... ");

        // Save rollback
        $this->rollback->save(array_keys($installed));

        // Install configuration
        if ($this->app->isSlave() !== true) {
            $this->pkg_config->install($pkg->getAlias());
        }

        // Save package to registry
        $pkg->setVersion($latest_version);
        $this->pkg_store->save($pkg);

        // Remove tmp_dir
        $this->io->removeDir($tmp_dir);
        $this->cli->send("done.\r\n");
    }

}

