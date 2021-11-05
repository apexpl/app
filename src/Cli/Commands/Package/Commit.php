<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, NetworkHelper};
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Svn\SvnCommit;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Commit
 */
class Commit implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(AccountHelper::class)]
    private AccountHelper $account_helper;

    #[Inject(NetworkHelper::class)]
    private NetworkHelper $network_helper;

    #[Inject(SvnCommit::class)]
    private SvnCommit $svn_commit;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['m', 'file']);
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $commit_args = $cli->getCommitArgs();

        // Initialize package
        if (!$pkg = $this->initPackage($pkg_alias, $cli)) { 
            return;
        }

        // Commit
        $this->svn_commit->process($pkg, $commit_args);

        // Save package, if needed
        if ($pkg->isModified() === true) { 
            $this->pkg_store->save($pkg);
        }

        // Success message
        $cli->send("\r\nSuccessfully completed commit of package $pkg_alias.\r\n\r\n");
    }

    /**
     * Initialize package
     */
    public function initPackage(string $pkg_alias, Cli $cli):?LocalPackage
    {

        // Check for project
        if ($info = $this->redis->hgetall('config:project')) {
            $pkg_alias = $info['pkg_alias'];
        }

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist on local system, $pkg_alias");
            return null;
        } elseif (false === $pkg->isVersionControlled()) { 
            $cli->error("This package is not currently version controlled, hence can not commit to it.  Please first checkout the package first, see 'apex help package checkout' for details.");
            return null;
        }

        // Get repo, if needed
        if (($repo = $pkg->getRepo()) === null) { 
            $repo = $this->network_helper->getRepo();
            $pkg->setRepoAlias($repo->getAlias());
        }

        // Get author
        if ($pkg->getLocalUser() == '') { 
            $account = $this->account_helper->get();
            $pkg->setLocalUser($account->getUsername());

            // Update author, if blank
            if ($pkg->getAuthor() == '') { 
                $pkg->setAuthor($account->getUsername());
            }
        }

        // Return
        return $pkg;
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Commit',
            usage: 'package commit <PKG_ALIAS> [-m <MESSAGE.] [--file <COMMIT_FILE>]',
            description: 'Uploads and commits all unsaved changes to the SVN repository.  If a repository has not yet been created for the package, one will be automatically created.'
        );

        // Params
        $help->addParam('pkg_alias', 'The alias of the package to commit.');
        $help->addFlag('-m', 'Optional commit message.');
        $help->addFlag('--file', 'Optional location of file containing commit message.');
        $help->addExample('./apex commit my-shop -m "Initial Commit"');

        // Return
        return $help;
    }

}


