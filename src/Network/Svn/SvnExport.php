<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\Cli;
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Sys\Utils\Io;
use Apex\App\Exceptions\ApexSvnRepoException;

/**
 * Svn Export
 */
class SvnExport
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Io::class)]
    private Io $io;

    // Properties
    public string $svn_dir = '';
    public string $version = '';

    /**
     * Process
     */
    public function process(SvnRepo $svn, string $version = '', bool $dev = false):string
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
        $this->svn_dir = $dir_name;
        $this->version = $version;

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
        $this->cli->send("done ($num files / directories).\r\n");

        // Return
        return $tmp_dir;
    }

}


