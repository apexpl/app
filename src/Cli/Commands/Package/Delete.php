<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\{PackageManager, ProjectManager};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Delete package
 */
class Delete implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackageManager::class)]
    private PackageManager $pkg_manager;

    #[Inject(ProjectManager::class)]
    private ProjectManager $project_manager;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
    $opt = $cli->getArgs();
        $remote = $opt['remote'] ?? false;

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }

        // Confirm deletion
        if (true !== $cli->getConfirm("Are you sure you want to permanently delete the package '" . $pkg->getAlias() . "' from the local machine?")) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Delete from repository, if needed
        if ($remote == true) { 
            $this->network->setAuth($pkg->getLocalAccount());
            if (!$res = $this->network->post($pkg->getRepo(), 'repos/delete', ['pkg_serial' => $pkg->getSerial()])) { 
                $cli->error("Unable to delete repository from SVN repository, aborting process.");
                return;
            }
        }

        // Delete project, if needed
        if ($pkg->getType() == 'project') { 
            $this->project_manager->delete($pkg);
        }

        // Delete package
        $this->pkg_manager->delete($pkg, true);

        // Success message
        $cli->send("Successfully deleted the package, " . $pkg->getAlias() . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Package',
            usage: 'package delete <PKG_ALIAS> [--remote]',
            description: 'Deletes a package from the local machine.'
        );
        $help->addParam('pkg_alias', 'The alias of the package to delete.');
        $help->addFlag('--remote', "Has no value, and if present will also delete the package off the SVN repository.");
        $help->addExample('./apex package delete my-shop');

        // Return
        return $help;
    }

}

