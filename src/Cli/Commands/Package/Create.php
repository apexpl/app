<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\PackageManager;
use Apex\App\Network\Stores\{ReposStore, PackagesStore};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Create package
 */
class Create implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageManager::class)]
    private PackageManager $pkg_manager;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $store;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['access', 'repo']);
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $access = $opt['access'] ?? 'public';
        $repo_alias = $opt['repo'] ?? 'apex';
        $is_theme = $opt['theme'] ?? false;

        // Check for author
        $author = '';
        if (preg_match("/^([a-zA-z0-9_-]+)\/([a-zA-Z0-9_-]+)$/", $pkg_alias, $match)) { 
            $author = $match[1];
            $pkg_alias = $match[2];
        }

        // Perform checks
        if ($pkg_alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $pkg_alias)) { 
            $cli->error("Invalid package alias specified, can not contain spaces or special characters.");
            return;
        } elseif ($pkg = $this->store->get($pkg_alias)) { 
            $cli->error("Package already exists on this machine, $pkg_alias");
            return;
        } elseif (!in_array($access, ['public', 'commercial', 'private'])) { 
            $cli->error("Invalid access, $access.  Supported values are: public, commercial, private");
            return;
        } elseif (!$repo = $this->repo_store->get($repo_alias)) {  
            $cli->error("Repo does not exist with alias, $repo_alias");
            return;
        }

        // Check duplicate
        if (!$this->pkg_helper->checkDuplicate($pkg_alias, $repo)) { 
            return;
        }

        // Create package
        $this->pkg_manager->create($pkg_alias, $access, $author, $is_theme);
        $title_alias = $this->convert->case($pkg_alias, 'title');

        // Success message
        $cli->send("Successfully created new package $title_alias and the following directories are now available and version controlled:\r\n\r\n");
        if ($is_theme === true) { 
            $cli->send("    /views/themes/$pkg_alias\r\n");
            $cli->send("    /public/themes/$pkg_alias\r\n");
            $cli->send("    /etc/$title_alias\r\n");
        } else {
            $cli->send("    /etc/$title_alias\r\n");
            $cli->send("    /src/$title_alias\r\n");
            $cli->send("    /tests/$title_alias\r\n");
            $cli->send("    /docs/$title_alias\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Create Package',
            usage: 'package create <PKG_ALIAS> [--access (public|commercial|private)] [--theme]',
            description: 'creates a new package on the local machine, ready for development.'
        );

        // Add params
        $help->addParam('pkg_alias', "The alias of the package, can not contain spaces or special characters.  May be formatted as 'username/alias' if creating a package on another user's account.");
        $help->addFlag('--access', "The access level of the package, defaults to 'public'.  Supported values are: public, commercial, private");
        $help->addFlag('--theme', 'If present the package will be created as a theme.');
        $help->addExample('./apex package create my-shop');
        $help->addExample('./apex package create client/my-theme --theme');

        // Return
        return $help;
    }

}
