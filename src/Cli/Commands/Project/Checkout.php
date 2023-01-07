<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, PackageHelper};
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\Svn\SvnCheckoutProject;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\NetworkClient;
use Apex\Db\Mapper\ToInstance;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Checkout project
 */
class Checkout implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(SvnCheckoutProject::class)]
    private SvnCheckoutProject $svn_checkout;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Construcotr
     */
    public function __construct(
        private bool $auto_confirm = false
    ) {

    }

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $pkg_serial = $this->pkg_helper->getSerial(($args[0] ?? ''));
        $repo_alias = $opt['repo'] ?? 'apex';

        // Perform checks
        if ($project_alias = $this->redis->hget('config:project', 'pkg_alias')) {
            $cli->error("The project $project_alias is already created on this system.  You can only work with one project at a time.");
            return;
        } elseif ($pkg_serial == '' || !preg_match("/^[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_]+$/", $pkg_serial)) {
            $cli->error("Invalid project alias defined, $pkg_serial.  Must be formatted as author/project.");
            return;
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository does not exist with alias, $repo_alias");
            return;
        }

        // Check if user has read access to package
        $res = $this->network->post($repo, 'repos/check', [
            'pkg_serial' => $pkg_serial,
            'is_install' => 1
        ]);

        // Check response
        if ($res['exists'] === false) {
            $cli->error("The package does not exist on the repository, $pkg_serial");
            return;
        } elseif ($res['can_read'] === false) {

            // Get account
            $acct = $this->acct_helper->get();
            $res['local_user'] = $acct->getUsername();

            // Send api call to check access
            $this->network->setAuth($acct);
            $res = $this->network->post($repo, 'repos/check', [
                'pkg_serial' => $pkg_serial,
                'is_install' => 1
            ]);
        }

        // Check user has read access
        if ($res['can_read'] === false) {
            $cli->error("You do not have write access to the package, $pkg_serial hence can not check it out.");
            return;
        }

        // Confirm checkout
        if ($this->auto_confirm === false) {
            $cli->send("WARNING:  This operation will permanently delete all code and database tables currently installed on this system, and replace it will the contents of the project.\r\n\r\n");
            if (!$cli->getConfirm("Are you sure you want to continue?")) {
                $cli->send("Ok, goodbye.\r\n\r\n");
                return;
            }
        }

        // Get package object
        $res['repo_alias'] = $repo->getAlias();
        $pkg = ToInstance::map(LocalPackage::class, $res);

        // Checkout the project
        $this->svn_checkout->process($pkg, (bool) $res['has_staging'], $res['dbinfo']);

        // Success
        $cli->send("Successfully checked out the project '$pkg_serial', and the entire Apex installation directory is now under version control.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Checkout Project',
            usage: 'project checkout <PKG_ALIAS>',
            description: 'Checkout a project for development.  This will delete all code and database tables currently installed, and replace them with the contents of the project.'
        );

        $help->addParam('pkg_alias', 'The alias of the package / project to checkout.');
        $help->addExample('./apex project checkout my-project');

        // Return
        return $help;
    }

}


