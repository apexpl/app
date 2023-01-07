<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

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
            title: 'Project Commands',
            usage: 'project <SUB_COMMAND> [OPTIONS]',
            description: 'Create and manage project.  Unlike packages, a project is the full Apex installation including all installed packages, and optionally comes with a staging environemtn at https://PROJECT.USERNAME.apexpl.dev/'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('checkout', 'Checkout a previously created project, and put the local machine under version control.');
        $help->addParam('close', 'Close the existing project, and remove version control.');
        $help->addParam('create', 'Create a new project and optional staging environment.');
        $help->addParam('info', 'View basic information regarding the open project.');
        $help->addParam('sql', 'Execute a single SQL or connect to the staging environment database.');
        $help->addParam('sync', 'Activate / deactivate the SVN Sync feature, so this system will be automatically synced with all commits made to the project repository.');

        // Return
        return $help;
    }

}


