<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\Cli;
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Svn\SvnExport;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalRepo;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Sys\Utils\Io;

/**
 * Svn Checkout Project
 */
class SvnCheckoutProject
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(SvnExport::class)]
    private SvnExport $svn_export;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Migration::class)]
    private Migration $migration;

    /**
     * Process
     */
    public function process(LocalRepo $repo, string $pkg_serial):void
    {

        // Check if user has write access to package
        if (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $pkg_serial, 'can_write')) { 
            $this->cli->error("You do not have write access to the package, $pkg_serial hence can not check it out.");
            return;
        }

        // Export package
        $tmp_dir = $this->svn_export->process($pkg->getSvnRepo(), '', true);

        // Create prev_fs directory
        $prev_dir = SITE_PATH . '/.apex/prev_fs';
        $this->io->createBlankDir($prev_dir);

        // Transfer site_path files to prev_fs directory
        $files = scandir(SITE_PATH);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..', '.apex', '.svn'])) { 
                continue;
            }
            rename(SITE_PATH . '/' . $file, "$prev_dir/$file");
        }

        // Copy tmp_dir over to site_path
    $files = scandir($tmp_dir);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            rename("$tmp_dir/$file", SITE_PATH . '/' . $file);
        }

        // Get all packages
        $packages = $this->pkg_store->list();
        $packages = array_reverse($packages);

        // Remove migrations
        foreach ($packages as $pkg_alias => $vars) {
            $tmp_pkg = $this->pkg_store->get($pkg_alias);
            $this->migrations->remove($tmp_pkg);
        }

        // Re-install all migrations
        $packages = array_reverse($packages);
        foreach ($packages as $pkg_alias => $vars) {
            $tmp_pkg = $this->pkg_store->get($pkg_alias);
            $this->migration->install($tmp_pkg);
        }


