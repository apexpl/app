<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Smtp;

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
            title: 'SMTP Commands',
            usage: 'sys smtp <SUB-COMMAND> [<OPTIONS>]',
            description: 'Manage the rotating SMTP connections used by this system.',
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('add', 'Add new SMTP connection.');
        $help->addParam('list', 'List all configured SMTP connections.');
        $help->addParam('get', 'Get info of single SMTP connection.');
        $help->addParam('delete', 'Delete a single SMTP connection.');
        $help->addParam('purge', 'Delete all SMTP connections configured on this system.');
        $help->addParam('test', "Send test e-mail to a SMTP server.");

        // Examples
        $help->addExample('./apex sys smtp add sendgrid');
        $help->addExample('./apex sys smtp list');

        // Return
        return $help;
    }

}


