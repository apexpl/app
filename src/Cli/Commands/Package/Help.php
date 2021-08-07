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
            description: "Create, manage and develop with packages from the repositories on the local machine.  Run 'apex help package <SUB_COMMAND>' for details on any of the below commands."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add commands
        $help->addParam('add', 'Add files / directories outside of the version controlled directories to the package.');
        $help->addParam('checkout', 'Checkout a package from repository, and put it under version control readying it for development.');
        $help->addParam('close', 'Close a package currently under version control, and move files back into production locations replacing their symlinks.');
        $help->addParam('commit', 'Commit all changes made to a package to the repository.');
        $help->addParam('create', 'Create a new package on the local machine.');
        $help->addParam('delete', 'Delete a package from the local machine, and if desired, from repository as well.');
        $help->addParam('info', 'View general information on a package.');
        $help->addParam('install', 'Install one or more packages from repository onto the local machine.');
        $help->addParam('list', 'List all packages currently installed on the local machine.');
        $help->addParam('merge', 'Merge a branch into the current working directory of the package.');
        $help->addParam('pull', "Update and sync a locally version controlled package with the repository.");
        $help->addParam('require', 'Install and require a Composer or Apex dependency to a package.');
        $help->addParam('rm', "remove files / directories from version control that were previously added via 'add' command.");
        $help->addParam('rollback', 'Rollback previously installed upgrades.');
        $help->addParam('scan', 'Scan the package.yml configuration file of a package for changes, and update the database accordingly.');
        $help->addParam('search', 'Search a repository for any available packages matching given term.');
        $help->addParam('update', 'Update general package information within the repository (name, access, price, et al)');
        $help->addParam('upgrade', 'Check for, download and install any available upgrades.');

        // Return
        return $help;
    }

}

