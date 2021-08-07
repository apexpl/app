<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Adapters\MigrationsConfig;
use Apex\Migrations\Handlers\ClassManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Migration status
 */
class Status implements CliCommandInterface
{

    #[Inject(MigrationsConfig::class)]
    private MigrationsConfig $config;

    #[Inject(ClassManager::class)]
    private ClassManager $manager;


    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get options
        $opt = $cli->getArgs(['package']);
        $package = $opt['package'] ?? '';

        // Get packages
        if ($package == '') { 
            $packages = array_keys($this->config->getPackages());
        } else { 
            $packages = [$package];
        }

        // Go through packages
        $total=0;
        foreach ($packages as $package) { 

            // Scan directory
            $res = $this->manager->scanPackageDirectory($package);
            if (count($res['pending']) == 0) { 
                continue;
            }

            // Show pending
            $cli->send("\r\nFound " . count($res['pending']) . " pending migrations for package " . $package . ":\r\n");
            foreach ($res['pending'] as $secs => $name) { 
                $cli->send("      $name\r\n");
            }
            $total += count($res['pending']);
        }
        // Send response
        if ($total > 0) {
            $cli->send("\r\nThere are a total of $total pending migrations awaiting installation.  You may install all pending migrations with:\r\n\r\n");
            $cli->send("      apex-migrations migrate\r\n\r\n");
        } else {
            $cli->send("No pending migrations found.  Database is up to date.\r\n\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Migrations Status',
            usage: 'migration status [--package=]',
            description: 'Shows the status and any pending migrations that are awaiting installation.'
        );

        $help->addFlag('--package', 'Optional package alias to display only the system of a single package instead of the overall system.');
        $help->addExample('./apex migration status');

        // Return
        return $help;
    }

}


