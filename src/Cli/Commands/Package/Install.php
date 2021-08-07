<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, PackageHelper};
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Models\{LocalRepo, LocalPackage};
use Apex\App\Network\Stores\ReposStore;
use Apex\Db\Mapper\ToInstance;
use Apex\App\Network\Svn\SvnInstall;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Install package
 */
class Install implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(SvnInstall::class)]
    private SvnInstall $svn_install;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['repo']);
        $repo_alias = $opt['repo'] ?? 'apex';
        $dev = $opt['dev'] ?? false;
        $noverify = $opt['noverify'] ?? false;

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this system, $repo_alias");
            return;
        }

        // Generate installation queue
        $install_queue = [];
        foreach ($args as $pkg_alias) { 
            $pkg_alias = $this->pkg_helper->getSerial($pkg_alias);
            if (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $pkg_alias, 'can_read')) { 
                $cli->error("You do not have access to download the package '$pkg_alias'");
                return;
            }
            $install_queue[] = $pkg;
        }

        // Go through install queue
        foreach ($install_queue as $pkg) { 

            // Install
            $svn = $pkg->getSvnRepo();
            $this->svn_install->process($svn, '', $dev, $noverify);

            // Success message
            $cli->send("Successfully installed the package, " . $pkg->getAlias() . ".\r\n\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Install Package',
            usage: 'package install <PKG_ALIAS> [<PKG_ALIAS2>] [<PKG_ALIAS3>] [--repo=apex] [--noverify]',
            description: 'Download and install packages.'
        );

        // Params
        $help->addParam('pkg_alias', "one or more package aliases to install.  You may specify multiple packages separated by a space.");
        $help->addFlag('--repo', "The repository alias to download the package from, defaults to the main public 'apex' repository.");
        $help->addFlag('--dev', "Does not have a value, and if present the dev-mater / trunk branch of the package will be downloaded.");
        $help->addFlag('--noverify', 'Does not have a value, and if present no digital signature verification checks will be processed during installation.');

        // Examples
        $help->addExample('./apex install webapp users transaction support');
        $help->addExample('./apex install jsmith/cool-shop --noverify');
        $help->addExample('./apex install jsmith/cool-shop --repo companyxyz');

        // Return
        return $help;
    }

}


