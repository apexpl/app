<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Db\ConnectionManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Get read-only db
 */
class GetReadonly implements CliCommandInterface
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
            $cli->error("You must specify an alias to get read-only connection information.  See 'apex help sys db get-readonly' for details.");
            return;
        }

        // Get
        if (!$info = $this->manager->getDatabase('read', $alias)) { 
            $cli->error("No read-only database exists with the alias, $alias");
            return;
        }

        // Send database info
        $cli->sendHeader("Read-Only Database - $alias");
        $cli->send("Name: $info[dbname]\n");
        $cli->send("User: $info[user]\n");
        $cli->send("Pass: $info[password]\n");
        $cli->send("Host:  $info[host]\n");
        $cli->send("Port: $info[port]\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Get Read=Only Database',
            usage: 'sys db get-readonly <ALIAS>',
            description: 'Get connection information for a read-only database connection.',
            params: [
                'alias' => 'Alias of the database connection to list connection info of.'
            ], 
            examples: [
                './apex sys db get-readonly db3'
            ]
        );

        // Return
        return $help;
    }

}


