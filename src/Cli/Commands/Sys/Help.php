<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

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
            title: 'System Commands',
            usage: 'sys <SUB_COMMAND> [OPTIONS]',
            description: 'Various system commands for tasks including manage the database(s), SMTP server(s), repositories, reset redis, and more.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add commands
        $help->addParam('db', 'Manage database connection information for master and read-only connections.');
        $help->addParam('repo', 'List and manage repositories configured on this system.');
        $help->addParam('smtp', 'List and manage SMTP e-mail servers configured on this system.');
        $help->addParam('compile-core', 'Used by maintainers of Apex to compile the core Github repository.');
        $help->addParam('crontab', 'Execute one or all pending crontab jobs.');
        $help->addParam('get-config', 'View one or more configuration variables.');
        $help->addParam('list', 'List all developer defined services or crontab jobs on system.');
        $help->addParam('listen', 'Used for horizontal scaling, and will begin listening on RabbitMQ or other message broker for incoming RPC calls.');
        $help->addParam('reset-redis', 'Reset the redis keys for one or all packages.');
        $help->addParam('scan-classes', 'Scan all lisenters and index all listeners, child classes, and implementors.');
        $help->addParam('set-config', 'Set the value of a configuration variable.');
        $help->addParam('sql', 'Execute SQL statement against database.');
        $help->addParam('svn', 'Execute SVN commands against a checked out package.');

        // Return
        return $help;
    }

}



