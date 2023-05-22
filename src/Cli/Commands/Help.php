<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands;

use Apex\App\Cli\{CliHelpScreen, Cli, Shortcuts};

/**
 * Main help
 */
class Help
{

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Apex CLI Tool', 
            usage: '<COMMAND> <SUB-COMMAND> [OPTIONS]', 
            description: "Below shows all top-level commands available.  Type 'apex help <COMMAND>' for details on the sub-commands available within."
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set available commands
        $commands = [
            'account' => 'Manage your Apex account(s).',
            'acl' => 'Manage access to repositories and signing certificates.',
            'branch' => 'Create and manage branches on repositories.',
            'create' => 'Create components (views, http controllers, tables, et al)',
            'gpt' => 'Code generation with Chat GPT assistance',
            'image' => 'Create and manage installation images.',
            'migration' => 'Create and manage database migrations.',
            'opus' => 'Code generation utilities (models, crud, et al)',
            'package' => 'Create and manage packages, checkout, commit, et al.',
            'project' => 'Create and manage projects / staging environments.',
            'release' => 'Create and manage releases of packages.',
            'svn' => 'Pass arguments directly to SVN for a package.',
            'sys' => 'Various system commands (config, smtp, database, et al).'
        ];

        // Check for shortcuts
        $opt = $cli->getArgs();
        $is_shortcuts = $opt['s'] ?? false;
        if ($is_shortcuts === true) {
            $top_level = array_flip(Shortcuts::$top_level);

            $new_commands = [];
            foreach ($commands as $key => $value) {
                $new_key = $top_level[$key] ?? $key;
                $new_commands[$new_key] = $value;
            }
            $commands = $new_commands;

            // Go through skip top level
            $help->setFlagsTitle('ADDITIONAL SHORTCUTS');
            foreach (Shortcuts::$skip_top_level as $source => $dest) {
                $help->addFlag($source, '-> ' . implode(' ', $dest));
            }
        }

        // Add commands
        foreach ($commands as $key => $value) {
            $help->addParam($key, $value);
        }

        // Flag / example
        if ($is_shortcuts === false) {
            $help->addFlag('-s', 'Get list of shortcut commands available.');
            $help->addExample('apex help -s )to see shortcuts)');
        }

        // Return
        return $help;
    }

}


