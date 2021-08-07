<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\Db\ConnectionManager;

/**
 * Set config
 */
class SetMaster extends AbstractDbCommand implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get database info
        list($args, $opt) = $cli->getArgs(['dbname','user','password','host','port']);
        $dbinfo = $this->getDatabaseInfo($cli, $opt);

        // Add connection
        $this->manager->addDatabase('write', $dbinfo);

        // Send response
        $cli->send("Successfully set master database information.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Set Master Database',
            usage: 'sys db set-master [--dbname=] [--user=] [--password=] [--host=] [--port=]',
            description: 'Set connection information of the master SQL database.  All flags are optional, and you will be prompted for any necessary information.',
            flags: [
                '--dbname' => 'Optional database name.',
                '--user' => 'Optional database username.',
                '--password' => 'Optional database password.',
                '--host' => 'Optional database host.',
                '--port' => 'Optional database port.'
            ], 
            examples: [
                './apex sys db set-master'
            ]
        );

        // Return
        return $help;
    }

}


