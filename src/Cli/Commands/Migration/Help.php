<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

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
            title: 'Migration Commands',
            usage: "migration <SUB_COMMAND> [OPTIONS]",
            description: "Install and rollback database migrations."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add params
        $help->addParam('create', 'Create a new database migration.');
        $help->addParam('history', 'View history of previously installed migrations.');
        $help->addParam('install', 'Run installation migration on package.  Used during development of a package.');
        $help->addParam('install-email', "Install e-mail notifications within package.yml of package.");
        $help->addParam('migrate', 'Install pending migrations.');
        $help->addParam('rollback', 'Rollback previously installed migrations.');
        $help->addParam('status', 'View current status and any pending migrations awaiting installation.');

        // Return
        return $help;
    }

}


