<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * List acls
 */
class Ls implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get account
        $account = $this->acct_helper->get();
        $repo = $account->getRepo();

        // Send request
        $this->network->setAuth($account);
        $res = $this->network->get($repo, 'acl/get');

        // Send header
        $cli->sendHeader('Access List');

        // Managed accounts
        if (count($res['manager']) > 0) { 
            $cli->send("You are a manager on the following accounts:\r\n\r\n");
            foreach ($res['manager'] as $line) { 
                $cli->send("    $line\r\n");
            }
            $cli->send("\r\n");
        }

        // Packages
        if (count($res['package']) > 0) { 
            $cli->send("You have access to the following package:\r\n\r\n");
            foreach ($res['package'] as $line) { 
                $cli->send("    $line\r\n");
            }
            $cli->send("\r\n");
        }

        // Branches
        if (count($res['branch']) > 0) { 
            $cli->send("You have access to the following branches:\r\n\r\n");
            foreach ($res['branch'] as $line) { 
                $cli->send("    $line\r\n");
            }
            $cli->send("\r\n");
        }

        // Own packages
        if (count($res['own_package']) > 0) { 
            $cli->send("You are owner of the following packages:\r\n\r\n");
            foreach ($res['own_package'] as $line) { 
                $cli->send("    $line\r\n");
            }
            $cli->send("\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'View Access List',
            usage: 'acl list',
            description: 'Displays list of all managed accounts, packages and branches you have been granted access to.'
        );
        $help->addExample('./apex acl list');

        // Return
        return $help;
    }

}

