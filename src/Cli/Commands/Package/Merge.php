<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Merge
 */
class Merge implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs();
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $branch_name = $this->convert->case(($args[1] ?? ''), 'lower');
        $dry_run = $opt['dry-run'] ?? false;

        // Check for project
        if (($info = $this->redis->hgetall('config:project')) && !$pkg = $this->pkg_store->get($pkg_alias)) {
            $branch_name = $pkg_alias;
            $pkg_alias = $info['pkg_alias'];
        }


        // Get package
        if (!$pkg = $this->pkg_helper->get($pkg_alias)) { 
            return;
        } elseif ($pkg->isVersionControlled() !== true) { 
            $cli->error("The package '$pkg_alias' is not currently version controlled.  Please checkout the package first, see 'apex help package checkout' for details.");
            return;
        }
        $svn = $pkg->getSvnRepo();
        $url = $svn->getSvnUrl('branches/' . $branch_name, false);

        // Set options
        $options = [$url];
        if ($dry_run === true) { 
            $options[] = '--dry-run';
        }

        // Check current branch
        if ($svn->getCurrentBranch() != 'trunk') { 
            $svn->switch('trunk');
        }

        // Merge
        $svn->setTarget('', 0, true);
        $res = $svn->exec(['merge'], $options);

        // Success
        $cli->send("Successfully merged branch '$branch_name' into the package '$pkg_alias'\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Merge Branch into Package',
            usage: 'package merge <PKG_ALIAS> <BRANCH> [--dry-run]',
            description: 'Merge a branch into the main /trunk/ of a package.'
        );

        $help->addParam('pkg_alias', 'The alias of the package to merge into.');
        $help->addParam('branch_name', 'The name of the branch to merge into /trunk');
        $help->addFlag('--dry-run', 'Has no value, and if present will do a dry run of the merge without making any modifications to /trunk.');
        $help->addExample('./apex package merge myshop new-feature');

        // Return
        return $help;
    }

}



