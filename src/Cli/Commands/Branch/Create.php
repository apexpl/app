<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Branch;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Create branch
 */
class Create implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['from-branch']);
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $branch_name = $this->convert->case(($args[1] ?? ''), 'lower');
        $from_branch = $this->convert->case(($opt['from-branch'] ?? 'trunk'), 'lower');

        // Check for project
        if (($info = $this->redis->hgetall('config:project')) && !$pkg = $this->pkg_store->get($pkg_alias)) {
            $branch_name = $pkg_alias;
            $pkg_alias = $info['pkg_alias'];
        }

        // Load package
        if (!$pkg = $this->pkg_helper->get($pkg_alias)) { 
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
        if ($svn->info() !== null) {
            $cli->error("The branch already exists, $branch_name");
            return;
        } elseif ($from_branch != 'trunk' && $svn->getInfo('branches/' . $from_branch) === null) { 
            $cli->error("The from branch does not exist, $from_branch");
            return;
        }

        // Check from branch
        if ($from_branch != 'trunk') { 
            $from_branch = 'branches/' . $from_branch;
        }

        // Send API call
        $this->network->setAuth($pkg->getLocalAccount());
        $res = $this->network->post($pkg->getRepo(), 'repos/create_branch', [
            'pkg_serial' => $pkg->getAuthor() . '/' . $pkg_alias,
            'branch_name' => $branch_name
        ]);

        // Create branch
        $svn->copy($from_branch, 'branches/' . $branch_name, ['-m', 'Creating branch']);
        $svn->switch('branches/' . $branch_name);

        // Success
        $http_url = 'https://' . $pkg->getRepo()->getHttpHost() . '/' . $pkg->getSerial() . '/branches/' . $branch_name;
        $cli->sendHeader("Successfully Created Branch");
        $cli->send("Successfully created new branch on package $pkg_alias with name, $branch_name which may now be found at:\r\n\r\n");
        $cli->send("    Web: $http_url\r\n");
        $cli->send("    SVN: " . $svn->getSvnUrl('branches/' . $branch_name, true) . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Branch',
            usage: 'branch create <PKG_ALIAS> <BRANCH_NAME> [--from-branch=]',
            description: 'Create a new branch of a package on the repository.'
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias to create a branch on.');
        $help->addParam('branch_name', 'The name of the branch to create.');
        $help->addFlag('--from-branch', 'Optional name of the branch to copy from.  Defaults to trunk / master branch.');
        $help->addExample('./apex branch create my-shop some-cool-feature');
        $help->addExample('./apex branch create my-shop another-new-feature --from-branch some-cool-feature');

        // Return
        return $help;
    }

}


