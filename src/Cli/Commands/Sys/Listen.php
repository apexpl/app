<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\{App, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\Cluster\Listener;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Listen
 */
class Listen implements CliCommandInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Instantiate listener
        $instance_name = $this->app->getInstanceName();
        $listener = $this->cntr->make('Listener::class, ['instance_name' => $instance_name]);

        // Listen
        $cli->send("Listening for RPC calls...\r\n\r\n");
        $listener->listen();

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Listen as RPC Server',
            usage: 'sys listen',
            description: 'Only applicable if running a RPC / queue server via RabbitMQ utilizing Cluster.  This will begin listening on RabbitMQ for incoming RPC calls.'
        );
        $help->addExample('./apex sys listen');

        // Return
        return $help;
    }

}



