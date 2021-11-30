<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Package;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Svn\SvnFileConverter;
use Apex\App\Sys\Utils\Io;
use Apex\App\Attr\Inject;

/**
 * Diff
 * 
 * Only used during checkout within Apex\App\Network\Svn\SvnCheckout if merkle roots do not 
 * match between local filesystem and remote repo, and user opted to use files
 * from SVN repo or to rename files.
 */
class Diff
{

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $file_converter;

    #[Inject(Io::class)]
    private Io $io;


    /**
     * Handle remote
     */
    public function processRemote(LocalPackage $pkg, array $diff):void
    {

        // Initialize
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();

        // Go through all files
        foreach ($diff as $file => $hash) {

            // Convert file
            $local_file = $this->file_converter->toLocal($pkg, $file);

            // Remove SVN file / dir
            $this->io->rm("$svn_dir/$file");

        // Copy over
            $this->io->copy(SITE_PATH . '/' . $local_file, "$svn_dir/$file");
        }

    }

        /**
     * Process rename
     */
    public function processRename(LocalPackage $pkg, array $diff):void
    {

        // Go through files
        foreach ($diff as $file => $hash) {

            // Convert file
            $local_file = $this->file_converter->svnToLocal($file);
            $local_file = SITE_PATH . '/' . $local_file;

            // Rename
            $this->renameFile($local_file);
        }

        // Process remote
        $this->processRemote($pkg, $diff);
    }

    /**
     * Rename file
     */
    private function renameFile(string $local_file):void
    {

        // Initialize
        $x=1;
        $backup_file = $local_file . '.bak';

        // Get filename
        do { 

            if (is_dir($local_file) && is_dir($backup_file)) { 
                $x++;
                $backup_file = $local_file . '.bak' . $x;
            } elseif (file_exists($local_file) && file_exists($backup_file)) { 
                $x++;
                $backup_file = $local_file . '.bak' . $x;
            } else { 
                break;
            }

        } while (true);

        // Rename
        $this->io->rename($local_file, $backup_file);
    }

}

