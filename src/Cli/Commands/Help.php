<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands;

use Apex\App\Cli\CliHelpScreen;

/**
 * Main help
 */
class Help
{

    /**
     * Help
     */
    public function help():CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Apex CLI Tool', 
            usage: '<COMMAND> <SUB-COMMAND> [OPTIONS]', 
            description: "Below shows all top-level commands available.  Type 'apex help <COMMAND>' for details on the sub-commands available within."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add parameters
        $help->addParam('account', 'Manage your Apex account(s).');
        $help->addParam('acl', 'Manage access to repositories and signing certificates.');
        $help->addParam('branch', 'Create and manage branches on repositories.');
        $help->addParam('create', 'Create components (views, http controllers, tables, et al)');
        $help->addParam('image', 'Create and manage installation images.');
        $help->addParam('migration', 'Create and manage database migrations.');
        $help->addParam('opus', 'Code generation utilities (models, crud, et al)');
        $help->addParam('package', 'Create and manage packages, checkout, commit, et al.');
        $help->addParam('project', 'Create and manage projects / staging environments.');
        $help->addParam('release', 'Create and manage releases of packages.');
        $help->addParam('svn', 'Pass arguments directly to SVN for a package.');
        $help->addParam('sys', 'Various system commands (config, smtp, database, et al).');

        // Flag / example
        $help->addFlag('-s', 'Get list of shortcut commands available.');
        $help->addExample('apex help -s )to see shortcuts)');

        // Return
        return $help;
    }

}


