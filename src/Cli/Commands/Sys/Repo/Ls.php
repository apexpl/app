<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Repo;

use Apex\App\CLi\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * List repos
 */
class Ls implements CliCommandInterface
{

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get repos
        $repos = $this->repo_store->list();
        $rows = [['Alias', 'Http Host', 'Name']];

        // Go through repos
        foreach ($repos as $alias => $vars) { 
            $rows[] = [$alias, $vars['http_host'], $vars['name']];
        }

        // Send table
        $cli->sendTable($rows);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Repositories',
            usage: 'sys repo list',
            description: 'List all repositories configured on this system.'
        );
        $help->addExample('./apex sys repo list');

        // Return
        return $help;
    }

}



