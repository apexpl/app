<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Db\ConnectionManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Purge read-only db
 */
class PurgeReadonly implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Purge databases
        $this->manager->deleteAll();

        // Send message
        $cli->send("Successfully purged all read-only databases.\r\n\r\n");
    
}
    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Purge Read=Only Databases',
            usage: 'sys db purge-readonly',
            description: 'Purge all read-only database connections.',
            examples: [
                './apex sys db purge-readonly'
            ]
        );

        // Return
        return $help;
    }

}


