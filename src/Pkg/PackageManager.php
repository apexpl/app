<?php
declare(strict_types = 1);

namespace Apex\App\Pkg;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\Cli;
use Apex\Opus\Opus;
use Apex\App\Sys\Utils\Io;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Pkg\Filesystem\Package\Remover;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Attr\Inject;

/**
 * Package
 */
class PackageManager
{

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(Registry::class)]
    private Registry $registry;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Cli::class)]
    private Cli $cli;

    /**
     * Create
     */
    public function create(string $alias, string $access = 'public', string $author = '', bool $is_theme = false):LocalPackage
    {

        // Initialize
        $name = $this->convert->case($alias, 'phrase');
        $type = $is_theme === true ? 'theme' : 'package';

        // Build via Opus
        $this->opus->build('package', SITE_PATH, [
            'alias' => $alias,
            'type' => $type,
            'access' => $access,
            'name' => $name
        ]);

        // Create package instance
        $pkg = $this->cntr->make(LocalPackage::class, [
            'is_local' => true,
            'type' => $type,
            'alias' => $alias,
            'author' => $author
        ]);

        // Save package
        $this->pkg_store->save($pkg);

        // Create theme, if needed
        if ($is_theme === true) { 

            // Build theme
            $this->opus->build('theme', SITE_PATH, ['alias' => $alias]);

            // Add theme dirs to registry
            $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $alias]);
            $registry->add('ext_files', 'views/themes/' . $alias);
            $registry->add('ext_files', 'public/themes/' . $alias);
        }

        // return
        return $pkg;
    }

    /**
     * Delete package
     */
    public function delete(LocalPackage $pkg, bool $verbose = false):void
    {

        // Remove migration
        $migration = $this->cntr->make(Migration::class);
        $migration->remove($pkg);

        // Remove files and directories
        $remover = $this->cntr->make(Remover::class);
        $remover->remove($pkg, $verbose);

        // Delete from db
        $this->pkg_store->delete($pkg->getAlias());
        if ($verbose === true) { 
            $this->cli->send("Removing package from registry...\r\n\r\n");
        }

    }

}



