<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Branch;

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
            title: 'Branch Commands',
            usage: 'branch <SUB_COMMAND>',
            description: 'Create and manage the various branches on packages.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add params
        $help->addParam('create', 'Create a new branch.');
        $help->addParam('delete', 'Delete a branch.');
        $help->addParam('list', 'List all branches on a package.');
        $help->addParam('switch', 'Switch to a branch.');

        // Return
        return $help;
    }

}


