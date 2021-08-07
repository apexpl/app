<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Package;

use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Exceptions\ApexPackageNotExistsException;

/**
 * Close package
 */
class Installer
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Cli::class)]
    private Cli $cli;

    // Properties
    private bool $verbose = false;

    /**
     * Close package
     */
    public function install(LocalPackage $pkg, string $svn_dir = '', bool $verbose = false):bool
    {

        // Initialize
        $pkg_alias = $pkg->getAliasTitle();
        $this->verbose = $verbose;

        // Get SVN directory
        if ($svn_dir == '') { 
            $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();
        }

        // Check svn dir
        if (!is_dir($svn_dir)) { 
            throw new ApexPackageNotExistsException("Unable to install / close package '$pkg_alias' as the local SVN directory does not exist at, $svn_dir");
        }

        // Install base dirs
        foreach (['src','etc','docs','tests'] as $dir) { 
            $local_dir = SITE_PATH . "/$dir/$pkg_alias";

            // Delete link, if needed
            if (is_link($local_dir)) { 
                unlink($local_dir);
            }

            // Rename directory, as needed
            if (is_dir("$svn_dir/$dir")) { 
                rename("$svn_dir/$dir", $local_dir);
            } elseif (!is_dir($local_dir)) { 
                mkdir($local_dir, 0755, true);
            }

            // Verbose
            if ($this->verbose === true) { 
                $msg_file = ltrim(str_replace(SITE_PATH , '', $local_dir), '/');
                $this->cli->send("Installing base directory... $msg_file\r\n");
            }
        }

        // Install registry
        $this->installRegistry($pkg, $svn_dir);

        // Clean up
        $ok = $this->cleanUp($svn_dir);
        return $ok;
    }

    /**
     * Install registry
     */
    private function installRegistry(LocalPackage $pkg, string $svn_dir):void
    {

        // Get registry
        $registry = $pkg->getRegistry();

        // Go through views
        $views = $registry['views'] ?? [];
        foreach ($views as $view) { 
            $this->doFile("$svn_dir/views/html/$view.html", SITE_PATH . "/views/html/$view.html");
            $this->doFile("$svn_dir/views/php/$view.php", SITE_PATH . "/views/php/$view.php");
        }

        // HTTP controllers
        $http_controllers = $registry['http_controllers'] ?? [];
        foreach ($http_controllers as $controller) { 
            $this->doFile("$svn_dir/share/HttpControllers/$controller.php", SITE_PATH . "/src/HttpControllers/$controller.php");
        }

        // Go through ext files
        $ext_files = $registry['ext_files'] ?? [];
        foreach ($ext_files as $file) { 
            $this->doFile("$svn_dir/ext/$file", SITE_PATH . '/' . $file);
        }

    }

    /**
     * Transfer single file
     */
    private function doFile(string $svn_file, string $local_file):void
    {

        // Strip slashes
        $svn_file = rtrim($svn_file, '/');
        $local_file = rtrim($local_file, '/');

        // Check file exists
        if ( (!file_exists($svn_file)) && (!is_dir($svn_file)) ) {
            return;
        }

        // Delete link, if exists
        if (is_link($local_file)) { 
            unlink($local_file);
        }

        // Create parent directory, if needed
        if (!is_dir(dirname($local_file))) { 
            mkdir(dirname($local_file), 0755, true);
        }

        // Verbose
        if ($this->verbose === true) { 
            $msg_file = ltrim(str_replace(SITE_PATH , '', $local_file), '/');
            $this->cli->send("Installing...  $msg_file\r\n");
        }

        // Rename
        rename($svn_file, $local_file);

// Check for and delete blank parent dirs
        $parent_dir = dirname($svn_file);
        do { 

            $files = scandir($parent_dir);
            if (count($files) > 2 || in_array('.svn', $files)) { 
                break;
            }

            // Remove dir
            rmdir($parent_dir);
            $parent_dir = dirname($parent_dir);
        } while (true);

    }

    /**
     * Clean up
     */
    private function cleanUp(string $svn_dir):bool
    {

        // Verbose
        if ($this->verbose === true) { 
            $this->cli->send("Cleaning up temporary directory... ");
        }

        // Check additional dirs
        foreach (['ext','share'] as $dir) { 

            if (!is_dir("$svn_dir/$dir")) { 
                continue;
            }

            // Check files
            $files = scandir("$svn_dir/$dir");
            if (count($files) > 2) { 
                continue;
            }
            $this->io->removeDir("$svn_dir/$dir");
        }

        // Check svn directory
        if (is_dir($svn_dir)) { 
            $files = scandir($svn_dir);
            if (count($files) > 3) { 
                return false;
            }
            $this->io->removeDir($svn_dir);
        }

        // Verbose
        if ($this->verbose === true) { 
            $this->cli->send("done.\r\n");
        }

        // Return
        return true;
    }

}

