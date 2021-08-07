<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Grant access to branch
 */
class GrantBranch implements CliCommandInterface
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
        $branch_name = $args[2] ?? '';
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
        } elseif ($branch_name == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $branch_name)) { 
            $cli->error("Invalid branch name, $branch_name");
            return;
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with alias, $repo_alias");
            return;
        }

        // Send API call
        $this->network->setAuth($account);
        $res = $this->network->post($repo, 'acl/grant_branch', [
            'pkg_serial' => $pkg_serial,
            'username' => $username,
            'branch_name' => $branch_name
        ]);

        // Success
        $cli->send("Successfully granted access to the branch '$branch_name' of the package '$pkg_serial' to the user '$username'.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Grant User Branch',
            usage: 'acl grant-branch <PKG_SERIAL> <USERNAME> <BRANCH> [--repo=<REPO>]',
            description: "Grants user write access to a branch.",
        );

        // Params
        $help->addParam('pkg_serial', 'The package serial (author/alias) of the package to add access to.');
        $help->addParam('username', 'The username of the person to provide access to.');
        $help->addParam('branch', 'The branch name to grant access to.');
        $help->addFlag('repo', 'The repository alias to grant access to.  Defaults to main apex repository.');
        $help->addExample('./apex acl grant-branch myuser/shop jsmith cool-feature');

        // Return
        return $help;
    }

}


