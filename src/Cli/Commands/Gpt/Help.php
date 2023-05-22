<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\App\Cli\{Cli, CliHelpScreen};

/**
 * Help
 */
class Help
{

    /**
     * Help screen
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Chat GPT Commands',
            usage: 'gpt <SUB_COMMAND>',
            description: 'Automatically generate code including database schema, controllers, models, views for any software system.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add params
        $help->addParam('generate', 'Generate all code components for a software system.');
        $help->addParam('init', 'Initialize Chat GPT API');

        // Return
        return $help;
    }

}


