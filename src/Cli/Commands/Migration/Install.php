<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Install package migration
 */
class Install implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Migration::class)]
    private Migration $migration;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs();
        $is_refresh = $opt['r'] ?? false;

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }

        // Remove, if needed
        if ($is_refresh === true) { 
            $this->migration->remove($pkg);
        }

        // Install migration
        $this->migration->install($pkg);

        // Success
        $cli->send("Successfully installed initial migration for package, " . $pkg->getAlias() . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Install Initial Migration',
            usage: 'migration install <PKG_ALIAS> [-r]',
            description: 'Installs the initial package migration.  Used during development in place of manually executing the SQL against the database.'
        );

        $help->addParam('pkg_alias', 'The alias of the package to install initial migration of.');
        $help->addFlag('-r', 'If present, the removal migration will also be run first.  Used to refresh the database.');
        $help->addExample('./apex migration install my-shop');

        // Return
        return $help;
    }

}


