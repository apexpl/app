<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Repo;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\NetworkHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Add repo
 */
class Add implements CliCommandInterface
{

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(NetworkHelper::class)]
    private NetworkHelper $network_helper;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $host = $args[0] ?? '';
        if ($host == '') { 
            $cli->error("You did not specify a host to add.");
            return;
        }

        // Add repo
        if (true === $this->network_helper->addRepo($host)) { 
            $cli->send("Successfully added new repository to system, $host\r\n\rn");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Add Repository',
            usage: 'sys repo add <HOST>',
            description: 'Configure a new repository on the system.'
        );

        $help->addParam('host', 'The HTTP host of the repository to add.');
        $help->addExample('./apex sys repo add new-repo.com');

        // Return
        return $help;
    }

}


