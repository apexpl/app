<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Filesystem\Package\Installer;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Close package
 */
class Close implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Installer::class)]
    private Installer $installer;

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

        // Check package exists
        if (!is_dir(SITE_PATH . '/.apex/svn/' . $pkg_alias)) { 
            $cli->error("This package is not currently under version control, $pkg_alias");
            return;
        }

        // Close package
        if (!$this->installer->install($pkg)) {
            $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias; 
            $cli->send("The SVN directory at $svn_dir is not empty, and should be manually checked.  Package not successfully fully closed.\r\n\r\n");
        } else { 
            $cli->send("Successfully closed the package '$pkg_alias', and it is no longer version controlled.\r\n\r\n");
        }
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {
        
        $help = new CliHelpScreen(
            title: 'Close Package', 
            usage: 'package close <PKG_ALIAS>', 
            description: 'Closes the local working copy of the SVN directory, and moves all files back into production location replacing their symlinks.'
        );

        // Add params
        $help->addParam('pkg_alias', 'The alias of the package to close.');
        $help->addExample('./apex package close my-shop');

        // Return
        return $help;
    }


}


