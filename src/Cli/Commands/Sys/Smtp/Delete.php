<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\Mercury\Email\RedisManager;
use Apex\App\Attr\Inject;

/**
 * Delete SMTP Server
 */
class Delete implements CliCommandInterface
{

    #[Inject(RedisManager::class)]
    private RedisManager $manager;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Delete
        $alias = $args[0] ?? '';
        $this->manager->deleteServer($alias);

        // Send message
        $cli->send("Successfully deleted the SMTP server, $alias.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Delete SMTP Server',
            usage: 'sys smtp delete <ALIAS>',
            description: 'Delete SMTP server.',
            params: [
                'alias' => 'The alias of the SMTP connection to delete.'
            ],
            examples: [
                './apex sys smtp delete sendgrid'
            ]
        );

        // Return
        return $help;
    }

}


