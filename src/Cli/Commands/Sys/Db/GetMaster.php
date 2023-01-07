<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Db\ConnectionManager;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Get master db
 */
class GetMaster implements CliCommandInterface
{

    #[Inject(ConnectionManager::class)]
    private ConnectionManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        if (!$info = $this->manager->getDatabase('write')) { 
            $cli->error("There is currently no master database configured.  You may set the master database by running, 'apex sys db set-master'.");
            return;
        }

        // Display info 
        $cli->sendHeader('Master Database');
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
            title: 'Get Master Database',
            usage: 'sys db get-master',
            description: 'Get the connection information for the master database.',
            examples: [
                './apex sys db get-master'
            ]
        );

        // Return
        return $help;
    }

}


