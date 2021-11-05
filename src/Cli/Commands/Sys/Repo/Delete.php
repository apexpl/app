<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Repo;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Delete repo
 */
class Delete implements CliCommandInterface
{

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get repo
        $repo_alias = $args[0] ?? '';
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repositorty does not exist with the alias, $repo_alias");
            return;
        }

        // Delete
        $this->repo_store->delete($repo_alias);

        // Send message
        $cli->send("Successfully removed the repository, $repo_alias\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Repository',
            usage: 'sys repo delete <ALIAS>',
            description: 'Delete a repository from the system.'
        );

        $help->addParam('alias', 'The alias of the repository to delete.');
        $help->addExample('./apex sys repo delete some-repo');

        // Return
        return $help;
    }

}


