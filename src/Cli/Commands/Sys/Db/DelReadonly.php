<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Db\ConnectionManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Delete read-only db
 */
class DelReadonly implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get database
        $alias = $args[0] ?? '';
        if ($alias == '') { 
            $cli->error("You must specify an alias of database connection to delete.  See 'apex help sys db del-readonly' for details.");
            return;
        }

        // Delete
        $this->manager->deleteDatabase($alias);

        // Sned message
        $cli->send("Successfully deleted database connection, $alias\r\n\r\n");
    }
    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Read=Only Database',
            usage: 'sys db del-readonly <ALIAS>',
            description: 'Delete a read-only database connection.',
            params: [
                'alias' => 'Alias of the database connection to delete.'
            ], 
            examples: [
                './apex sys db del-readonly db3'
            ]
        );

        // Return
        return $help;
    }

}


