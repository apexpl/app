<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Purge SMTP servers
 */
class Purge implements CliCommandInterface
{

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Confirm
        if (!$cli->getConfirm("Are you sure you want to delete all SMTP servers configured on this system?", 'n')) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Delete
        $keys = $this->redis->keys("config:mercury.smtp_server*");
        foreach ($keys as $key) { 
            $this->redis->del($key);
        }

        // Send message
        $cli->send("Successfully deleted all SMTP servers from this system.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Purge SMTP Servers',
            usage: 'sys smtp purge',
            description: 'Delete all SMTP servers from this system.',
            examples: [
                './apex sys smtp purge'
            ]
        );

        // Return
        return $help;
    }

}


