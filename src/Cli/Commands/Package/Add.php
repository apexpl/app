<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Container;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Add file
 */
class Add implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        array_shift($args);

        // Check version controlled
        if ($pkg->isVersionControlled() !== true) { 
            $cli->error("The package '$pkg_alias' is not version controlled.  Please first checkout the package, see 'apex help package checkout' for details.");
            return;
        }

        // Load registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias . '/ext';

        // Go through files
        foreach ($args as $file) { 
            $file = trim($file, '/');

            // Check if exists
            if ( (!is_dir(SITE_PATH . '/' . $file)) && (!file_exists(SITE_PATH . '/' . $file)) ) { 
                $cli->send("File does not exist at, /$file\r\n");
                continue;
            }

            // Create parent directory, if needed
            if (!is_dir(dirname("$svn_dir/$file"))) { 
                mkdir(dirname("$svn_dir/$file"), 0755, true);
            }

            // Rename
            rename(SITE_PATH . '/' . $file, "$svn_dir/$file");
            symlink("$svn_dir/$file", SITE_PATH . '/' . $file);

            // Add to registry
            $registry->add('ext_files', $file);
            $cli->send("Added $file\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Add File',
            usage: 'add <PKG_ALIAS> <FILE1> <FILE2> ...',
            description: 'Add file / directory to package under version control.'
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias to add file / directory to.');
        $help->addParam('file', 'List of files / directories to add to package.');
        $help->addExample('./apex package add my-shop public/images/shop.png public /js/shop');

        // Return
        return $help;
    }

}

