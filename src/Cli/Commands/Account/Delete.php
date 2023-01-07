<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\{AccountsStore, PackagesStore};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Delete account
 */
class Delete implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(AccountsStore::class)]
    private AccountsStore $store;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get account
        $alias = $args[0] ?? '';
        if ($alias == '' || !$acct = $this->store->get($alias)) { 
            $acct = $this->acct_helper->get();
        }

        // Confirm deletion
        $name = $acct->getUsername() . ' @ ' . $acct->getRepoAlias() . ' <' . $acct->getEmail() . '>';
        if (!$cli->getConfirm("This action will remove the account '$name' from the local machine.  Are you sure you wish to delete this account?")) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Delete acct
        $this->store->delete($acct);

        // Send message
        $cli->send("Successfully deleted the following accounts from the local machine:\r\n\r\n    $name\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Delete Account',
            usage: 'account delete [<ALIAS>]',
            description: 'Deletes an account from the local machine.'
        );

        // return
        return $help;
    }
}



