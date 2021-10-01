<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Remove file
 */
class Rm implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Io::class)]
    private Io $io;

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

        // Check
        if ($pkg->isVersionControlled() !== true) { 
            $cli->error("The package '$pkg_alias' is not version controlled.  Please first checkout the package, see 'apex help package checkout' for details.");
            return;
        } elseif (count($args) == 0) { 
            $cli->error("You did not specify any files to remove.");
            return;
        }

        // Load registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias . '/ext';

        // Go through files
        foreach ($args as $file) { 
            $file = trim($file, '/');

            // Remove from registry
            if (!$registry->remove('ext_files', $file)) { 
                $cli->send("External file is not registered to package, $file\r\n");
                continue;
            }

            // Check if exists
            if ( (!is_dir("$svn_dir/$file")) && (!file_exists("$svn_dir/$file")) ) { 
                $cli->send("File does not exist at, /$file\r\n");
                continue;
            }

            // Create parent directory, if needed
            if (!is_dir(dirname(SITE_PATH . '/' . $file))) { 
                mkdir(dirname(SITE_PATH . '/' . $file), 0755, true);
            }

            // Remove link, if it exists
            if (is_link(SITE_PATH . '/' . $file)) { 
                unlink(SITE_PATH . '/' . $file);
            }
            $this->io->rename("$svn_dir/$file", SITE_PATH . '/' . $file);

            $cli->send("Removed $file\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Remove File',
            usage: 'rm <PKG_ALIAS> <FILE1> <FILE2> ...',
            description: 'Remove file / directory from package under version control.'
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias to remove file / directory from.');
        $help->addParam('file', 'List of files / directories to remove from package.');
        $help->addExample('./apex package rm my-shop public/images/shop.png public /js/shop');

        // Return
        return $help;
    }

}

