<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * List packages
 */
class Ls implements CliCommandInterface
{

    #[Inject(PackagesStore::class)]
    private PackagesStore $store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // List packages
        $packages = $this->store->list();
        $rows = [['Alias','Version','Author','Install Date']];

        // Go through packages
        foreach ($packages as $pkg_alias => $vars) { 
            $author = $vars['author'] ?? '';
            $rows[] = [$pkg_alias, $vars['version'], $author, date('M-d H:i', $vars['installed_at'])];
        }

        // Send table
    $cli->sendHeader('Installed Packages');
        $cli->sendTable($rows);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Packages',
            usage: 'package list',
            description: 'Lists all packages installed on the local machine.'
        );
        $help->addExample('./apex package list');

        // Return
        return $help;
    }

}

