<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Package;

use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\LocalPackage;
use Apex\Opus\Opus;
use Apex\App\Attr\Inject;

/**
 * Remove package
 */
class Remover
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Remove
     */
    public function remove(LocalPackage $pkg, bool $verbose = false):void
    {

        // Remove registry
        $this->removeRegistry($pkg, $verbose);

        // Delete via Opus
        $this->opus->remove('package', SITE_PATH, ['alias' => $pkg->getAlias()]);
        if ($verbose === true) { 
            $this->cli->send("Removing base directories...\r\n");
        }

        // Delete SVN directory, if exists
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();
        if (is_dir($svn_dir)) { 
            $this->io->removeDir($svn_dir);
            if ($verbose === true) { 
                $this->cli->send("Removing local SVN working directory...\r\n");
            }
        }

    }

    /**
     * Remove registry
     */
    private function removeRegistry(LocalPackage $pkg, bool $verbose = false):void
    {

        // Get registry
        $registry = $pkg->getRegistry();

        // Delete views
        $views = $registry['views'] ?? [];
        foreach ($views as $view) { 
            $this->opus->remove('view', SITE_PATH, ['uri' => $view]);
            if ($verbose === true) { 
                $this->cli->send("Removing view...  $view\r\n");
            }
        }

        // Http controllers
        $http_controllers = $registry['http_controllers'] ?? [];
        foreach ($http_controllers as $alias) { 
            $this->opus->remove('http_controller', SITE_PATH, ['alias' => $alias]);
            if ($verbose === true) { 
                $this->cli->send("Removing http controller... $alias\r\n");
            }
        }

        // External files
        $ext_files = $registry['ext_files'] ?? [];
        foreach ($ext_files as $file) { 

            if (is_dir(SITE_PATH . '/' . $file)) { 
                $this->io->removeDir(SITE_PATH . '/' . $file);
            } elseif (file_exists(SITE_PATH . '/' . $file)) { 
                $this->io->removeFile(SITE_PATH . '/' . $file, true);
            }

            if ($verbose === true) { 
                $this->cli->send("Removing file / directory...  $file\r\n");
            }
        }

    }

}


