<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Revoke manager access
 */
class RevokeManager implements CliCommandInterface
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

        // Initialize
        $username = $args[0] ?? '';

        // Get account
        $account = $this->acct_helper->get();
        $repo = $account->getRepo();

        // Perform checks
        if ($username == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $username)) { 
            $cli->error("Invalid username specified, $username");
            return;
        }

        // Send API call
        $this->network->setAuth($account);
        $res = $this->network->post($repo, 'acl/revoke_manager', [
            'username' => $username
        ]);

        // Success
        $cli->send("Successfully revoked manager access from user '$username'.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Revoke Manager',
            usage: 'acl revoke-manager <USERNAME>',
            description: "Remove a user from manager access."
        );

        // Params
        $help->addParam('username', 'The username of the manager to revoke access of.');
        $help->addExample('./apex acl revoke-manager jsmith');

        // Return
        return $help;
    }

}



