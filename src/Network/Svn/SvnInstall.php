<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\Container;
use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Sign\VerifyDownload;
use Apex\App\Network\Stores\PackagesStore;
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

    #[Inject(VerifyDownload::class)]
    private VerifyDownload $verifier;

    #[Inject(Installer::class)]
    private Installer $installer;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Migration::class)]
    private Migration $migration;

    /**
     * Install
     */
    public function process(SvnRepo $svn, string $version = '', bool $dev = false, bool $noverify = false)
    {

        // Get latest release
        if ($version == '' && $dev === false) { 
            $this->cli->send("Determining latest version... ");
            if (!$version = $svn->getLatestRelease()) { 
                throw new ApexSvnRepoException("Unable to determine latest release of package, " . $svn->getPackage()->getAlias() . ".  Use --dev option to download /trunk branch.");
            }
            $this->cli->send("v$version\r\n");
        }
        $dir_name = $dev === true ? 'trunk' : 'tags/' . rtrim($version, '/') . '/';

        // Get tmp directory
        $tmp_dir = sys_get_temp_dir() . '/apex-' . uniqid();
        if (is_dir($tmp_dir)) { 
            $this->io->removeDir($tmp_dir);
        }

        // Export package
        $this->cli->send("Downloading package... ");
        $svn->setTarget($dir_name, 0, false, false);
        if (!$res = $svn->exec(['export'], [$tmp_dir])) { 
            $svn->setTarget($dir_name);
            $res = $svn->exec(['export'], [$tmp_dir]);
        }

        // Check for error
        if (!$res) { 
            throw new ApexSvnRepoException("Unable to export package from SVN, error: " . $svn->error_output);
        }

        // Get number of files / dirs
        $num = substr_count($res, "\nA");
        $this->cli->send("done ($num files / directories).\r\nVerifying digital signature... ");

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

        // Success
        $this->cli->send("done.\r\nInstallation complete.\r\n\r\n");
    }

}


