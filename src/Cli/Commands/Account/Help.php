<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\App\Cli\CliHelpScreen;

/**
 * Account Help
 */
class Help
{

    /**
     * Help
     */
    public function help():CliHelpScreen
    {

        // Get help screen
        $help = new CliHelpScreen(
            title: 'Account Commands',
            usage: 'account <SUB-COMMAND> [OPTIONS]',
            description: "Manage your Apex account(s), including register a new account, manage and update existing accounts, replace and revoke signing certificates, et al.  Type 'apex help account <SUB-COMMAND>' for help on any of the below commands."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('import', 'Import an existing Apex account to the local machine.');
        $help->addParam('register', 'Register a new Apex account.');

        // Return
        return $help;
    }
}


