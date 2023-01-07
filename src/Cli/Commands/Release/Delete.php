<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Release;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Delete release
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
     8 Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $version = $args[1] ?? '';
        $commit_args = $cli->getCommitArgs();

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif (null === ($svn = $pkg->getSvnRepo())) { 
            $cli->error("The package does not have a repository assigned to it.  Please commit the package first, see 'apex help package commit' for details.");
            return;
        }

        // Ensure release exists
        $releases = $svn->getReleases();
        if ($version == '' || !in_array($version, $releases)) { 
            $cli->error("The release $pkg_alias v$version does not exist within the repository.");
            return;
        }

        // Confirm deletion
        if (true !== $cli->getConfirm("Are you sure you want to permanently delete the release of $pkg_alias v$version?")) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Send API call
        $this->network->setAuth($pkg->getLocalAccount());
        $res = $this->network->post($pkg->getRepo(), 'repos/delete_release', [
            'pkg_serial' => $pkg->getSerial(),
            'version' => $version
        ]);

        // Delete from repo
        $svn->setTarget('tags/' . $version);
        $svn->rmdir('tags/' . $version, $commit_args);

        // Send message
        $cli->send("Successfully deleted the release of $pkg_alias v$version\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Delete Release',
            usage: 'release delete <PKG_ALIAS> <VERSION>',
            description: 'Delete a previously created release.'
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to delete release from.');
        $help->addParam('version', 'The version number of the release to delete.');
        $help->addExample('./apex release delete myshop 2.6.3');

        // Return
        return $help;
    }

}


