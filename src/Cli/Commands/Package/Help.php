<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

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
            title: 'Package Commands',
            usage: 'package <SUB_COMMAND> [OPTIONS]',
            description: "Create, manage and develop packages from the repositories on the local machine.  Run 'apex help package <SUB_COMMAND>' for details on any of the below commands."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add commands
        $help->addParam('add', 'Add external files to a package.');
        $help->addParam('checkout', 'Checkout a package and put it under version control.');
        $help->addParam('close', 'Close a package currently under version control.');
        $help->addParam('commit', 'Commit all changes made to a package to the repository.');
        $help->addParam('create', 'Create a new package on the local machine.');
        $help->addParam('delete', 'Delete a package from the local machine.');
        $help->addParam('fork', 'Fork a package from the repository.');
        $help->addParam('init-theme', 'Initialize a new theme during development / integration.');
        $help->addParam('info', 'View general information on a package.');
        $help->addParam('install', 'Install one or more packages from the repository.');
        $help->addParam('list', 'List all packages currently installed on the local machine.');
        $help->addParam('merge', "Merge a branch from repository into local package.");
        $help->addParam('pull', "Update and sync a local package with the repository.");
        $help->addParam('require', 'Install and require a Composer or Apex dependency.');
        $help->addParam('rm', "remove files previousy added with 'add' command.");
        $help->addParam('rollback', 'Rollback previously installed upgrades.');
        $help->addParam('scan', 'Scan package.yml configuration file and update database.');
        $help->addParam('search', 'Search a repository for packages matching a given term.');
        $help->addParam('test', 'Execute unit tests of a package.');
        $help->addParam('update', 'Update general package information within the repository.');
        $help->addParam('upgrade', 'Check for, download and install any available upgrades.');

        // Return
        return $help;
    }

}

