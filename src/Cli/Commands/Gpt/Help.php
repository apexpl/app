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
            description: 'Automatically generate code and components including database schema, controllers, models, views for any software system.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add params
        $help->addParam('auto-complete', 'Generate auto-complete component with AI assistance.');
        $help->addParam('form', 'Generate form component with AI assistance.');
        $help->addParam('init', 'Initialize Chat GPT API');
        $help->addParam('package', 'Create new package, and generate everything necessary from plain text including SQL database schema, models, views, controllers, menu, etc.');
        $help->addParam('rest-api', 'Generate REST API endpoints from plain text.'); 
        $help->addParam('table', 'Generate data table component with AI assistance.');

        // Return
        return $help;
    }

}


