<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;

/**
 * Execute unit tests
 */
class Test implements CliCommandInterface
{

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
        $pkg_alias = $pkg->getAliasTitle();

        // Set args
        $args = [
            './vendor/bin/phpunit',
            '--bootstrap',
            './boot/init/tests.php',
            SITE_PATH . '/tests/' . $pkg_alias . '/'
        ];

        // Run process
        $process = new Process($args);
        $process->setWorkingDirectory(SITE_PATH);
        $process->run(function ($type, $buffer) { 
            fputs(STDOUT, $buffer);
        });

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Execute Unit Tests',
            usage: 'package test <PKG_ALIAS>',
            description: 'Execute all unit tests on a specific package.'
        );

        $help->addParam('pkg_alias', 'The alias of the package to execute unit tests of.');
        $help->addExample('./apex package test myshop');

        // Return
        return $help;
    }

}


