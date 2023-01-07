<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Release;

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
            title: 'Release Commands',
            usage: 'release <SUB_COMMAND> [OPTIONS]',
            description: "Tag and manage releases of your packages.  See 'apex help release <SUB_COMMAND>' for details on any of the below commands."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set params
        $help->addParam('changelog', 'View changelog between two releases.');
        $help->addParam('create', 'Create / tag new release of a package.');
        $help->addParam('delete', 'Delete a release.');
        $help->addParam('list', 'List all releases created on a package.');

        // Return
        return $help;
    }

}

