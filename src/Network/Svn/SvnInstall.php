<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\Container;
use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Svn\SvnExport;
use Apex\App\Network\Sign\VerifyDownload;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Pkg\Filesystem\Package\Installer;
use Apex\App\Exceptions\ApexSvnRepoException;

/**
 * Svn Install
 */
class SvnInstall
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(SvnExport::class)]
    private SvnExport $svn_export;

    #[Inject(VerifyDownload::class)]
    private VerifyDownload $verifier;

    #[Inject(Installer::class)]
    private Installer $installer;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Migration::class)]
    private Migration $migration;

    // Properties
    private bool $update_composer = false;

    /**
     * Install
     */
    public function process(SvnRepo $svn, string $version = '', bool $dev = false, bool $noverify = false)
    {

        // Export package
        $tmp_dir = $this->svn_export->process($svn, $version, $dev);
        $this->cli->send("Verifying digital signature... ");

        // Verify
        if ($noverify === true || $dev === true) { 
            $this->cli->send("Skipping verification checks, proceeding with installation...\r\n");
        } else { 
            if (!$signed_by = $this->verifier->verify($svn, $dir_name, $tmp_dir)) { 
                $this->cli->error("Unable to install package, as verification failed.");
                return false;
            }
            $this->cli->send("done (signed by: $signed_by).\r\nInstalling package... \n");
        }

        // Install
        $this->installer->install($svn->getPackage(), $tmp_dir, true);

        // Insert
        $pkg = $svn->getPackage();
        $pkg->setIsLocal(false);
        $pkg->setVersion($version);
        $this->pkg_store->save($pkg);

        // Initial migration
        $this->cli->send("Performing initial migration... ");
            $this->migration->install($pkg);

        // Install dependencies
        $this->cli->send("done.\r\nInstalling any needed dependencies... ");
        $this->installDependencies($pkg, $no_verify);

        // Update composer, if needed
        if ($this->update_composer === true) { 
            shell_exec("update composer");
        }

        // Success
        $this->cli->send("done.\r\nInstallation complete.\r\n\r\n");
    }

    /**
     * Install dependencies
     */
    private function installDependencies(LocalPackage $pkg, bool $no_verify):void
    {

        // Apex dependencies
        $yaml = $pkg->getConfig();
        $dependencies = $yaml['require'] ?? [];
        foreach (Dependencies as $pkg_alias => $version) { 

            // Check for latest version
            if ($version == '*') { 
                $version = '';
            }

            // Install package
            $this->process($pkg->getSvnRepo(), $version, false, $no_verify);
        }

        // Get composer dependencies
        $registry = $pkg->getRegistry();
        $dependencies = $registry['require_composer'] ?? [];
        if (count($dependencies) == 0) { 
            return;
        }

        // Load composer.json file
        $json = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true);

        // Go through dependencies
        foreach ($dependencies as $package => $version) { 
            if (isset($json['require'][$package])) { 
                continue;
            }
            $json['require'][$package] = $version;
            $this->update_composer = true;
        }

        // Save composer.json file
        file_put_contents(SITE_PATH . '/composer.json', json_encode($json, JSON_PRETTY_PRINT));
    }

}

