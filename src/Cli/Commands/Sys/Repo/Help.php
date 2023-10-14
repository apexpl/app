<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Repo;

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
            title: 'Repository Commands',
            usage: 'sys repo <SUB_COMMAND> [OPTIONS]',
            description: 'List and manage the repositories configured on this system.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add commands
        $help->addParam('add', 'Add a new repository.');
        $help->addParam('delete', 'Delete an existing repository.');
        $help->addParam('ls', 'List all repositories configured on this system.');

        // Return
        return $help;
    }

}



