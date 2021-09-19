<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

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
            title: 'ACL Commands',
            usage: 'acl <SUB_COMMAND> [OPTIONS]',
            description: "Grand and revoke access to your SVN repositories, and request access to other's repositories."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add commands
        $help->addParam('grant-branch', 'Grant access to a specific branch of a package.');
        $help->addParam('grant-manager', 'Grant another manager access over your Apex account.');
        $help->addParam('grant-package', 'Grant access to a package.');
        $help->addParam('list', "List the access you have to other's repositories.");
        $help->addParam('pending', "View and process all pending requests for access to your repositories.");
        $help->addParam('request-branch', "Request access to a specific ranch of a package.");
        $help->addParam('request-manager', "Request manager access to another user's Apex account.");
        $help->addParam('request-package', "Request access to another package.");
        $help->addParam('revoke-branch', "Revoke a user's access to a specific branch of a package.");
        $help->addParam('revoke-manager', "Revoke a user's manager access to your Apex account.");
        $help->addParam('revoke-package', "Revoke a user's access to a package.");

        // Return
        return $help;
    }

}


