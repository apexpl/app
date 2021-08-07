<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\AccountsStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * List Accounts
 */
class Ls implements CliCommandInterface
{

    #[Inject(AccountsStore::class)]
    private AccountsStore $store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get accounts
        $accounts = $this->store->list(true);
        $rows = [['Username', 'E-Mail', 'Repository', 'Date Created']];

        // Go through accounts
        foreach ($accounts as $alias => $cdate) { 
            $acct = $this->store->get($alias);
            $rows[] = [
                $acct->getUsername(),
                $acct->getEmail(),
                $acct->getRepoAlias(),
                $cdate
            ];
        }

        // Send header
        $cli->sendHeader('Account List');
        $cli->send("The below table lists all Apex accounts currently configured on this machine.\r\n\r\n");

        // Send table
        $cli->sendTable($rows);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Set help
        $help = new CliHelpScreen(
            title: 'List Accounts',
            usage: 'account list',
            description: 'Lists all Apex accounts configured on this machine.',
            examples: ['./apex account list']
        );

        // Return
        return $help;
    }


}


