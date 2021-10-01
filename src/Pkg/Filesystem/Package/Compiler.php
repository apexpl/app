<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Package;

use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Exceptions\ApexCompilerException;

/**
 * Package compiler
 */
class Compiler
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Cli::class)]
    private Cli $cli;

    // Properties
        private string $handle_diff = 'use_remote';
    private bool $verbose = false;

    /**
     * Compile package
     */
    public function compile(LocalPackage $pkg, string $handle_diff = 'use_local', bool $verbose = false):void
    {

        // Check for project
        if ($pkg->getType() == 'project') {
            return;
        }


        // Set variables
        $pkg_alias = $pkg->getAlias();
        $alias = $pkg->getAliasTitle();
        $this->handle_diff = $handle_diff;
        $this->verbose = $verbose;

        // Check local dir
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias;
        if (!is_dir($svn_dir)) { 
            throw new ApexCompilerException("Unable to complete initial compile of package '$pkg_alias', as the local SVN directory does not exist.  Please checkout the package first.");
        }

        // Move base dirs
        foreach (['src','etc','docs','tests'] as $dir) { 
            $this->doFile(SITE_PATH . "/$dir/$alias", "$svn_dir/$dir");
            if ($verbose === true) {
                $this->cli->send("Saving base directory... $dir/$alias\r\n");
            }
        }

        // Compile registry
        $this->compileRegistry($pkg, $svn_dir);
    }

    /**
     * Compile registry
     */
    private function compileRegistry(LocalPackage $pkg, string $svn_dir):void
    {

        // Get registry
        $registry = $pkg->getRegistry();

        // Go through views
        $views = $registry['views'] ?? [];
        foreach ($views as $view) { 
            $this->doFile(SITE_PATH . "/views/html/$view.html", "$svn_dir/views/html/$view.html");
            $this->doFile(SITE_PATH . "/views/php/$view.php", "$svn_dir/views/php/$view.php");

            if ($this->verbose === true) { 
                $this->cli->send("Saving view.. $view\r\n");
            } 
        }

        // HTTP controllers
        $http_controllers = $registry['http_controllers'] ?? [];
        foreach ($http_controllers as $alias) { 
            $this->doFile(SITE_PATH . "/src/HttpControllers/$alias.php", "$svn_dir/share/HttpControllers/$alias.php");
            if ($this->verbose === true) { 
                $this->cli->send("Saving http controller...  $alias\r\n");
            }
        }

        // Go through ext files
        $ext_files = $registry['ext_files'] ?? [];
        foreach ($ext_files as $file) { 
            $this->doFile(SITE_PATH . '/' . $file, "$svn_dir/ext/$file");
            if ($this->verbose === true) { 
                $this->cli->send("Saving file...  $file\r\n");
            }
        }

    }

    /**
     * Compile view
     */
    private function doFile(string $local_file, string $svn_file):void
    {

        // Trim slashes
        $local_file = rtrim($local_file, '/');
        $svn_file = rtrim($svn_file, '/');

        // Skip, if needed
        if (is_link($local_file) && readlink($local_file) == $svn_file) { 
            return;
        } elseif (is_link($local_file)) { 
            throw new ApexCompilerException("Found invalid link upon compiling package.  The link $local_file links to " . readlink($local_file) . ", but should be linking to $svn_file");
        } elseif ( (!file_exists($local_file)) && (!is_dir($local_file)) ) { 
            return;
        }

        // Ensure svn dir exists
        if (!is_dir(dirname($svn_file))) { 
            mkdir(dirname($svn_file), 0755, true);
        }

        // Handle diffs, if needed
        if ($this->handle_diff == 'use_local') { 
            $this->renameLocalToSvn($local_file, $svn_file);
        } elseif ($this->handle_diff == 'rename') { 
            $this->backupLocalFile($local_file);
        } elseif (is_dir($svn_file)) { 
            $this->io->removeDir($local_file);
        } elseif (file_exists($svn_file)) { 
            unlink($local_file);
        } else { 
            $this->io->rename($local_file, $svn_file);
        }
        symlink($svn_file, $local_file);
    }

    /**
     * Rename local to SVN
     */
    private function renameLocalToSvn(string $local_file, string $svn_file):void
    {

        // Delete from SVN, if needed
        if (is_dir($local_file) && is_dir($svn_file)) { 
            $this->io->removeDir($svn_file);
        } elseif (file_exists($local_file) && file_exists($svn_file)) { 
            unlink($svn_file);
        }

        // Rename
        $this->io->rename($local_file, $svn_file);
    }

    /**
     * Backup local file
     */
    private function backupLocalFile(string $local_file):void
    {


        // Get filename
        $x=1;
        $backup_file = $local_file . '.bak';
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

