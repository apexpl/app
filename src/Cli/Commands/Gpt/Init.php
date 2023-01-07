<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\Svc\App;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Init gpt3
 */
class Init implements CliCommandInterface
{

    #[Inject(App::class)]
    private App $app;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $api_key = $args[0] ?? '';
        if ($api_key == '') {
            $cli->error("You did not specify an OpenAI API Key.");
            return;
        }

        // Update config
        $this->app->setConfigVar('core.openai_apikey', $api_key);

        // Success
        $cli->send("Successfully set your OpenAI API key.  You may now begin automatically generating fully functional components by running the command:\n\n");
        $cli->send("    apex gpt generate\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Define help screen
        $help = new CliHelpScreen(
            title: 'Initialize Chat GPT',
            description: "Initialize the Chat GPT integration by defining your OpenAI API key.",
            usage: "gpt init <API_KEY"
        );

        // Params
        $help->addParam('api_key', "Your OpenAI API Key.");
        $help->addExample("./apex gpt init <API_KEY>");

        // Return
        return $help;
    }

}


