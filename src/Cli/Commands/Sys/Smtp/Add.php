<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\Mercury\Email\RedisManager;

/**
 * Add SMTP Server
 */
class Add implements CliCommandInterface
{

    #[Inject(RedisManager::class)]
    private RedisManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $info = $cli->getArgs(['host','port','user','password']);
        $info['is_ssl'] = 1;
        $alias = $args[0] ?? '';

        // Get smtp info
        $cli->sendHeader('Add SMTP Server');
        foreach (['host','port','user','password'] as $var) { 

            if (isset($info[$var]) && $info[$var] != '') { 
                continue;
            }
            $info[$var] = $cli->getInput(ucwords($var) . ': ');
        }

        // Add server
        $this->manager->addServer($info, $alias);

        // Send message
        $cli->send("Successfully added new SMTP server, $info[host]\r\n\rn");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Add SMTP Server', 
            usage: 'sys smtp add [--host=] [--port=] [--user=] [--password=]',
            description: 'Add a new SMTP server into the rotation.  All flags are optional, and you will be prompted for the necessary information.',
            params: [
                'alias' => 'Optional alias to save alias as for future management.'
            ],
            flags: [
                '--host' => 'Optional SMTP hostname.',
                '--port' => 'Optional SMTP port.',
                '--user' => 'Optional SMTP username.',
                '--password' => 'Optional SMTP password.'
            ],
            examples: [
                './apex sys smtp add sendgrid'
            ]
        );

        // Return
        return $help;
    }

}


