<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Image;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Delete installation image
 */
class Delete implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Check
        $name = $args[0] ?? '';
        if ($name == '') { 
            $cli->error("You did not specify an image alias to delete.");
            return;
        }

        // Get account and repo
        $acct = $this->acct_helper->get();
        $repo = $this->repo_store->get('apex');

        // Format name, if needed
        if (!str_contains($name, '/')) { 
            $name = $acct->getUsername() . '/' . $name;
        }

        // Get installation image
        $this->network->setAuth($acct);
        $res = $this->network->post($repo, 'images/get', ['serial' => $name]);

        // Check if image exists
        if ($res['exists'] != 1) { 
            $cli->error("No installation image exists with the alias, $name");
            return;
        }

        // Confirm deleteion
        if (!$cli->getConfirm("This will permanently delete the installation image '$name' from the repository.  Are you sure you wish to continue?")) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Delete
        $res = $this->network->post($repo, 'images/delete', ['serial' => $name]);

        // Send message
        $cli->send("Successfully deleted the installation image '$name' from the repository.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Installation Image',
            usage: 'image delete <ALIAS>',
            description: 'Delete an installation image off the repository.'
        );

        $help->addParam('alias', 'The alias of the installation image to delete');
        $help->addExample('./apex image delete ecommerce');

        // return
        return $help;
    }

}


