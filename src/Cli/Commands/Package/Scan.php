<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Scan package
 */
class Scan implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();

        // Load config
        $this->pkg_config->install($pkg_alias);

        // Success
        $cli->send("Successfully scanned the package, $pkg_alias\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Scan Package',
            usage: 'package scan <PKG_ALIAS>',
            description: "Scans the package's package.yml configuration file, and updates the database accordingly."
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias to scan configuration of.');
        $help->addExample('./apex package scan my-shop');

        // Return
        return $help;
    }

}


