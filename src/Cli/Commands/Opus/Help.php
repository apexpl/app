<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};

/**
 * Help
 */
class Help
{

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Opus Commands',
            usage: 'opus <SUB_COMMAND> [OPTIONS]',
            description: 'Variable code generation commands for models, collections, CRUD, API endpoints, and more.  All of these commands are optional, and only generate the code classes, nothing more.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('admin-settings', 'Generate views within the admin panel to define settings for a package.');
        $help->addParam('aip-endpoint', 'Generate a new REST API endpoint class.');
        $help->addParam('collection', 'Generate full collection with array and iterator functionality constrained to a single model class.');
        $help->addParam('email-controller', 'Create new e-mail notification controller class.');
        $help->addParam('html-tag', 'Create new HTML tag class allowing <s:tag> to work within templates.');
        $help->addParam('iterator', 'Create a new iterator class constrained to a single model class.');
        $help->addParam('model', 'Create a new model class from existing database table.');
        $help->addParam('stack', 'Create a new stack class.');

        // Return
        return $help;
    }

}


