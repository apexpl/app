<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

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

        // Start help
        $help = new CliHelpScreen(
            title: 'Database Commands',
            usage: 'sys db <SUB-COMMAND> [<OPTIONS>]',
            description: 'Manage the master and optional read-only database connections for this system.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('get-master', 'List the master database connection info.');
        $help->addParam('set-master', 'Set the master database connection info.');
        $help->addParam('list-readonly', 'List all read-only database connections configured.');
        $help->addParam('get-readonly', 'List connection information for a read-only database connection.');
        $help->addParam('set-readonly', 'Set / add a read-only database connection.');
        $help->addParam('del-readonly', 'Delete a read-only database connection.');
        $help->addParam('purge-readonly', 'Delete all read-only database connections.');
        $help->addParam('dump', 'Dump the SQL database to a text file.');

        // Examples
        $help->addExample('./apex sys db set-master');
        $help->addExample('./apex sys db set-readonly db3');

        // Return
        return $help;
    }

}


