<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\AccountsStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Get account
 */
class Get implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $helper;

    #[Inject(AccountsStore::class)]
    private AccountsStore $store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        list($args, $opt) = $cli->getArgs();
        $alias = $args[0] ?? '';

        // Get account
        if ($alias == '' || !$acct = $this->store->get($alias)) { 
            $acct = $this->helper->get();
        }

        // Get account info
        $this->network->setAuth($acct);
        $info = $this->network->post($acct->getRepo(), 'users/get', []);

        // Display profile
        $this->helper->display($info);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {
        return new CliHelpScreen(
            title: 'Get Account Profile',
            usage: 'account get [<ALIAS>]',
            description: 'View profile of an account configured on this machine.',
            params: [
                'alias' => 'Optional alias in form of USERNAME.REPO.  If unspecified, list of all accounts configured on this machine will be displayed to choose from.'
            ]
        );
    }
}


