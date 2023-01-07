<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\Svc\{App, HttpClient};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Generate GPT
 */
class Generate implements CliCommandInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get description / prompt
        $cli->sendHeader("GPT Code Generation");
        $cli->send("In plain text, define the functionality you would like automatically developed.  This will automatically generate the necessary models, views, menus, controllers, and more for the exact functionality.\n\n");
        $cli->send("Once done, leave a blank line by pressing enter key twice to start the code generation.\n\n");

        // Get input
        $prompt = $cli->getInput("What are you developing? ");
        do {
            $line = $cli->getInput("");
            if ($line == '') {
                break;
            }           $prompt .= "\n\n" . $line;
        } while (true);

        echo "$prompt\n";
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Define help scren
        $help = new CliHelpScreen(
            title: 'GPT Code Generation',
            description: 'Automatically generate all components for any functionality, including models, views, controllers, menus, et al.',
            usage: 'gpt generate <PKG_ALIAS>'
        );

    // Return
        return $help;
    }

}





