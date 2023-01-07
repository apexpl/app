<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Branch;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Switch branch
 */
class Sw implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $branch_name = $this->convert->case(($args[1] ?? ''), 'lower');

        // Check for project
        if (($info = $this->redis->hgetall('config:project')) && !$pkg = $this->pkg_store->get($pkg_alias)) {
            $branch_name = $pkg_alias;
            $pkg_alias = $info['pkg_alias'];
        }

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        } elseif ($pkg->getType() != 'project' && !is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) { 
            $cli->error("This package is not checked out.  Please first checkout the package, see 'apex help package checkout' for details.");
            return;
        } elseif ($branch_name == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $branch_name)) { 
            $cli->error("Invalid branch name, $branch_name");
            return;
        }

        // Format branch name, check for /trunk
        if ($branch_name != 'trunk') { 
            $branch_name = 'branches/' . $branch_name;
        }

        // Switch branch
        $svn->switch($branch_name);

        // Success
        $cli->send("Successfully switched branch on package '$pkg_alias' to $branch_name\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Switch Branch',
            usage: 'branch switch <PKG_ALIAS> <BRANCH_NAME>',
            description: 'Switch to another branch of a package.'
        );

        return $help;
    }

}


