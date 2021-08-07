<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Revoke package access
 */
class RevokePackage implements CliCommandInterface
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

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $pkg_serial = $args[0] ?? '';
        $username = $args[1] ?? '';
        $repo_alias = $opt['repo'] ?? 'apex';

        // Get account
        $account = $this->acct_helper->get();

        // Check pkg_serial
        if (!str_contains($pkg_serial, '/')) { 
            $pkg_serial = $account->getUsername() . '/' . $pkg_serial;
        }

        // Perform checks
        if ($pkg_serial == '' || !preg_match("/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/", $pkg_serial)) { 
            $cli->error("Invalid package specified, $pkg_serial.  Must be in format of author/alias.");
            return;
        } elseif ($username == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $username)) { 
            $cli->error("Invalid username specified, $username");
            return;
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with alias, $repo_alias");
            return;
        }

        // Send API call
        $this->network->setAuth($account);
        $res = $this->network->post($repo, 'acl/revoke_package', [
            'pkg_serial' => $pkg_serial,
            'username' => $username
        ]);

        // Success
        $cli->send("Successfully revoked roles from user '$username' from the package '$pkg_serial'.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Revoke User Role',
            usage: 'acl revoke-role <PKG_SERIAL> <USERNAME> [--repo=<REPO>]',
            description: "Removed an access role from a user on a package."
        );

        // Params
        $help->addParam('pkg_serial', 'The package serial (author/alias) of the package to revoke access from.');
        $help->addParam('username', 'The username of the person to revoke access of.');
        $help->addFlag('repo', 'The repository alias to revoke access from.  Defaults to main apex repository.');
        $help->addExample('./apex acl revoke-role myuser/shop jsmith');

        // Return
        return $help;
    }

}



