<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\Mercury\Email\RedisManager;
use Apex\App\Attr\Inject;
use redis;

/**
 * List SMTP Servers
 */
class Ls implements CliCommandInterface
{

    #[Inject(RedisManager::class)]
    private RedisManager $manager;

    #[Inject(Redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $cli->sendHeader('SMTP Servers');
        $cli->send("Below lists all SMTP servers currently configured on this system that are in rotation:\r\n\r\n");

        // Go through server
        $aliases = $this->redis->lrange('config:mercury.smtp_servers', 0, -1); 
        foreach ($aliases as $alias) { 

            // Get from redis
            if (!$vars = $this->redis->hgetall('config:mercury.smtp_servers.' . $alias)) { 
                continue;
            }
            $cli->send("    [$alias] $vars[host]:$vars[port]\r\n");
        }
        $cli->send("\r\n");

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'List SMTP Servers', 
            usage: 'sys smtp list',
            description: 'Lists all SMTP currently currently configured on this system.',
            examples: [
                './apex sys smtp list'
            ]
        );

        // Return
        return $help;
    }

}


