<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Package;

use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Svn\SvnFileConverter;

/**
 * Package inventory
 */
class Inventory
{

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $svn_convert;

    #[Inject(Io::class)]
    private Io $io;

    // Properties
        private LocalPackage $pkg;
    private array $inv = [];

    /**
     * Get inventory from local filesystem (not local SVN repo)
     */
    public function get(LocalPackage $pkg):array
    {

        // Initialize
        $this->pkg = $pkg;
        $this->inv = [];
        $pkg_alias = $pkg->getAliasTitle();

        // Go through base dirs
        foreach (['src','etc','tests','docs'] as $dir) { 

            // SKip, if directory not exists
            if (!is_dir(SITE_PATH . "/$dir/$pkg_alias")) { 
                continue;
            }

            // Go through files
            $files = $this->io->parseDir(SITE_PATH . "/$dir/$pkg_alias");
            foreach ($files as $file) { 
                $this->doFile("$dir/$pkg_alias/$file");
            }
        }

        // Add registry
        $this->addRegistry();

        // Return
        ksort($this->inv);
        return $this->inv;
    }

    /**
     * Add registry
     */
    private function addRegistry():void
    {

        // Get registry
        $registry = $this->pkg->getRegistry();

        // Go through views
        $views = $registry['views'] ?? [];
        foreach ($views as $view) { 
            $this->doFile("views/html/$view.html", true);
            $this->doFile("views/php/$view.php", true);
        }

        // HTTP controllers
        $http_controllers = $registry['http_controllers'] ?? [];
        foreach ($http_controllers as $controller) { 
            $this->doFile('src/HttpControllers/' . $controller . '.php');
        }

        // External files
        $ext_files = $registry['ext_files'] ?? [];
        while (count($ext_files) > 0) { 
            $file = trim(array_shift($ext_files), '/');

            // Check for directory
            if (is_dir(SITE_PATH . '/' . $file)) { 
                $tmp_files = $this->io->parseDir(SITE_PATH . '/' . $file);
                foreach ($tmp_files as $tmp_file) { 
                    $ext_files[] = $file . '/' . $tmp_file;
                }
                continue;

            } elseif (file_exists(SITE_PATH . '/' . $file)) { 
                $this->doFile($file);
            }
        }

    }

    /**
     * Process file
     */
    private function doFile(string $file, bool $is_registry = false):void
    {

        // Check file exists
        if (!file_exists(SITE_PATH . '/' . $file)) { 
            return;
        }

        // Add to inventory
        $svn_file = $this->svn_convert->toSvn($this->pkg, $file, $is_registry);
        $this->inv[$svn_file] = sha1_file(SITE_PATH . '/' . $file);
    }

}


