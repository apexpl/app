<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\Db\ConnectionManager;
use Apex\App\Attr\Inject;

/**
 * Set read only database
 */
class SetReadonly extends AbstractDbCommand implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        list($args, $opt) = $cli->getArgs(['dbname', 'user', 'password', 'host', 'port']);
        $alias = $args[0] ?? '';

        // Get alias, if needed
        if ($alias == '') { 
            $alias = $cli->getInput('Instance Name (eg. db2, nyc3, etc.): ');
        }

        // Check alias
        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias, it can only contain spaces or special characters.\r\n");
            return;
        }

        // Get database info
        $dbinfo = $this->getDatabaseInfo($cli, $opt);

        // Add connection
        $this->manager->addDatabase('read', $dbinfo, $alias);

        // Send response
        $cli->send("Successfully added new read-only database.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Set Read-Only Database',
            usage: 'sys db set-readonly [<ALIAS>] [--dbname=] [--user=] [--password=] [--host=] [--port=]',
            description: 'Set or overrite connection information of a read-only slave database.',
            params: [
                'alias' => 'The alias of the database connection, can be any alpha-numeric string.'
            ],
            flags: [
                '--dbname' => 'Optional database name.',
                '--user' => 'Optional database username.',
                '--password' => 'Optional database password.',
                '--host' => 'Optional database host.',
                '--port' => 'Optional database port.'
            ], 
            examples: [
                './apex sys db set-readonly db3'
            ]
        );

        // Return
        return $help;
    }

}


