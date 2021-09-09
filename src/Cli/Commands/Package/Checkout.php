<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\{PackagesStore, ReposStore};
use Apex\App\Network\Svn\{SvnCheckout, SvnInstall};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Checkout
 */
class Checkout implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(SvnCheckout::class)]
    private SvnCheckout $svn_checkout;

    #[Inject(SvnInstall::class)]
    private SvnInstall $svn_install;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['repo']);
        $pkg_alias = $this->pkg_helper->getSerial(($args[0] ?? ''));
        $repo_alias = $opt['repo'] ?? 'apex';

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 

            // Get repo
            if (!$repo = $this->repo_store->get($repo_alias)) { 
                $cli->error("Repository does not exist with alias, $repo_alias");
                return;
            }

            // Check if user has write access to package
            if (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $pkg_alias, 'can_write')) { 
                $cli->error("You do not have write access to the package, $pkg_alias hence can not check it out.");
                return;
            }

            // Install and load package
            $this->svn_install->process($pkg->getSvnRepo(), '', true);
            $pkg = $this->pkg_store->get($pkg_alias);
        }

        // Get SVN repo
        if (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        } elseif (is_dir(SITE_PATH . '/.apex/svn/' . $pkg_alias)) { 
            $cli->error("The package '$pkg_alias' is already checked out.  Please use update instead.");
            return;
        }

        // Checkout package
        $svn->checkout();

        // Success
        $cli->send("\nSuccessfully checked out the package '$pkg_alias', and it is now version controlled.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Checkout Package',
            usage: 'package checkout <PKG_ALIAS>',
            description: 'Checkout a locally installed package, and places it under version control in-sync with the SVN repository.'
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to checkout.');
        $help->addExample('./apex package checkout jsmith/my-shop');

        // Return
        return $help;
    }
}



