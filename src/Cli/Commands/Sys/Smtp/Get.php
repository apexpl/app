<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Add SMTP Server
 */
class Get implements CliCommandInterface
{

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get server
        $alias = $args[0] ?? '';
        if (!$info = $this->redis->hgetall('config:mercury.smtp_servers.' . $alias)) {
            $cli->error("No SMTP server exists at the alias, $alias.  You may view a list of configured aliases with 'apex sys smtp list'.");
            return;
        }

        // Send header
    $cli->sendHeader('SMTP Information');
        $cli->send("Below shows the details of the '$alias' SMTP server:\r\n\r\n");

        // Send server info
        $cli->send("    Host: $info[host]\r\n");
        $cli->send("    Port: $info[port]\r\n");
        $cli->send("    User: $info[user]\r\n");
        $cli->send("    Pass: $info[password]\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Get SMTP Server',
            usage: 'sys smtp get <ALIAS>',
            description: 'Get connection information of an SMTP server configured on this system.',
            params: [
                'alias' => 'The alias of the SMTP server to get.'
            ],
            examples: [
                './apex sys smtp get sendgrid'
            ]
        );

        // Return
        return $help;
    }

}


