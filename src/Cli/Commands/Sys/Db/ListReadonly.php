<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Db\ConnectionManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * List read-only db
 */
class ListReadonly implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get databases
        $dbs = $this->manager->listReadonly();

        // GO through databasse
        $cli->sendHeader('Read-Only Databases');
        foreach ($dbs as $alias => $name) { 
            $cli->send("    [$alias]  $name\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Read=Only Databases',
            usage: 'sys db list-readonly',
            description: 'List all read-only database connections.',
            examples: [
                './apex sys db list-readonly'
            ]
        );

        // Return
        return $help;
    }

}


