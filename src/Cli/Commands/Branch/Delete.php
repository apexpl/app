<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Branch;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Delete branch
 */
class Delete implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['m','file']);
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $branch_name = $this->convert->case(($args[1] ?? ''), 'lower');
        $message = $opt['m'] ?? '';
        $commit_file = $opt['file'] ?? '';

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        } elseif ($branch_name == '' || $branch_name == 'trunk' || !preg_match("/^[a-zA-z0-9_-]+$/", $branch_name)) { 
            $cli->error("Invalid branch name, $branch_name");
            return;
        }

        // Check if branch exists
        $svn->setTarget('branches/' . $branch_name);
        if ($svn->info() === null) {
            $cli->error("The branch does not exist, $branch_name");
            return;
        }

        // Confirm deletion
        $url = $svn->getSvnUrl('branches/' . $branch_name);
        if (!$cli->getConfirm("You are about to permanently delete the following branch:\r\n\r\n    Branch: $url\r\n\r\nAre you sure you want to continue?")) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Send API call
        $this->network->setAuth($pkg->getLocalAccount());
        $res = $this->network->post($pkg->getRepo(), 'repos/delete_branch', [
            'alias' => $pkg_alias, 
            'branch_name' => $branch_name
        ]);

        // Delete branch
        $svn->rmdir('branches/' . $branch_name, $message, $commit_file);

        // Success
        $cli->send("Successfully deleted branch from the package '$pkg_alias' with name, $branch_name\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Branch',
            usage: 'branch delete <PKG_ALIAS> <BRANCH_NAME> [-m <MESSAGE>] [--file=<COMMIT_FILE>]',
            description: 'Deletes a branch from the repository.'
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias from which to delete branch off.');
        $help->addParam('branch_name', 'The branch name to delete.');
        $help->addFlag('-m', 'Optional commit message.');
        $help->addFlag('--file', 'Optional location of file containing commit message.');
        $help->addExample('./apex branch delete my-shop some-cool-feature');
        $help->addExample('./apex branch delete myshop some-feature --file commit.txt');

        // Return
        return $help;
    }

}


