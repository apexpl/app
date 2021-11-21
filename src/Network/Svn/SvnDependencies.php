<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\Container;
use Apex\App\Cli\Cli;
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Models\{LocalRepo, LocalPackage};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Svn\SvnInstall;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\App\Exceptions\ApexDependencyException;

/**
 * Install dependencies
 */
class SvnDependencies
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    /**
     * Process
     */
    public function process(LocalRepo $repo, string $tmp_dir, bool $noverify = false, bool $is_local_repo = false):void
    {

        // Load yaml file
        try {
            $yaml = Yaml::parseFile("$tmp_dir/etc/package.yml");
        } catch (ParseException $e) { 
            $yaml = [];
        }
        $require = $yaml['require'] ?? [];

        // Go through required dependencies
        foreach ($require as $pkg_serial => $version) {
            $this->installPackage($repo, $pkg_serial, $version, $noverify, $is_local_repo);
        }

    }

    /**
     * Install Apex package
     */
    public function installPackage(LocalRepo $repo, string $pkg_serial, string $version, bool $noverify = false, bool $is_local_repo = false):void
    {

        // Check if package already installed
        if ($chk = $this->pkg_store->get($pkg_serial)) {
            return;
        }
        $pkg_serial = $this->pkg_helper->getSerial($pkg_serial);

        // Send message
        $this->cli->send("\nInstalling dependency $pkg_serial...\n");

        // Check package access
        if ($is_local_repo === true) {
            list($author, $pkg_alias) = explode('/', $pkg_serial, 2);
            $pkg = $this->cntr->make(LocalPackage::class, [
                'author' => $author,
                'repo_alias' => $repo->getAlias(),
                'alias' => $pkg_alias
            ]);

        } elseif (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $pkg_serial, 'can_read', true)) {
            throw new ApexDependencyException("You do not have permission to download the package, $pkg_serial.  If you do have access to this package, re-install Apex with the --import flag to import your account during installation.");
        }

        // Get version
        $dev = false;
        if ($version == 'latest') {
            $version = '';
        } elseif ($version == 'dev') {
            $version = '';
            $dev = true;
        }

        // Install package
        $svn_install = $this->cntr->make(SvnInstall::class);
        $svn_install->process($pkg->getSvnRepo(), $version, $dev, $noverify, $is_local_repo);
    }

}

