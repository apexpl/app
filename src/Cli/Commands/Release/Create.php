<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Release;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Sys\Utils\ScanClasses;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create release
 */
class Create implements CliCommandInterface
{

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(ScanClasses::class)]
    private ScanClasses $scan_classes;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        } elseif (!$pkg->isVersionControlled()) {
            $cli->error("The package is not currently under version control, hence a release can not be added.  Please see 'apex help package checkout' for details.");
            return;
        }

        // Set variables
        $pkg_alias = $pkg->getAlias();
        $commit_args = $cli->getCommitArgs();

        // Initialize
        $opt = $cli->getArgs(['m', 'file']);
        $version = $args[1] ?? '';
        $is_breaking = isset($opt['breaking']) && $opt['breaking'] === true ? 1 : 0;
        $commit_args = $cli->getCommitArgs();

        // Get version
        if ($version == '') { 
            $version = $cli->getInput("Release Version: ");
        }

        // Checks
        if (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        } elseif ($pkg->isVersionControlled() !== true) { 
            $cli->error("This package is not currently under version control, and can not be released.");
            return;
        } elseif (preg_match("/![\d\.]/", $version)) { 
            $cli->error("Invalid version number specified, $version");
            return;
        }

        // Check if release exists
        $cli->send("Checking previous releases... ");
        $svn->setTarget('tags/' . $version);
        if ($svn->info() !== null) {
            $cli->error("The release already exists, v$version");
            return;
        }
        $cli->send("done.\r\nScanning classes... ");

        // Scan classes
        $this->scan_classes->scan();
        $cli->send("done.\r\nVerifying access... ");

        // Send API call
        $this->network->setAuth($pkg->getLocalAccount());
        $res = $this->network->post($pkg->getRepo(), 'repos/create_release', [
            'pkg_alias' => $pkg_alias, 
            'version' => $version,
            'is_breaking' => $is_breaking === true ? 1 : 0
        ]);
        $cli->send("done.  Creating release, please wait a moment...\r\n\r\n");

        // Create release
        $old_dir = $svn->getCurrentBranch();
        $svn->copy('trunk', 'tags/' . $version, $commit_args);

        // Switch, and add properties
        $svn->switch('tags/' . $version);
        $svn->setProperty('is_breaking', (string) $is_breaking);

        // Commit
        $svn->setTarget('', 0, true);
        $svn->exec(['commit'], $commit_args);
        $svn->switch($old_dir);

        // Save package with new version
        $pkg->setVersion($version);
        $this->pkg_store->save($pkg);

        // Success
        $http_url = 'https://' . $pkg->getRepo()->getHttpHost() . '/' . $pkg->getSerial() . '/release/' . $version;
        $cli->sendHeader('Successfully Created Release');
        $cli->send("Successfully created new release on package '$pkg_alias' with version v$version.  You may view the release at:\r\n\r\n");
        $cli->send("    Web: $http_url\r\n");
        $cli->send("    SVN: " . $svn->getSvnUrl('tags/' . $version, true) . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Release',
            usage: 'release create <PKG_ALIAS> [<VERSION>] [--breaking] [-m <MESSAGE>] [--file <COMMIT_FILE>]',
            description: 'Creates a new release on a package.'
        );

        // Add params
        $help->addParam('pkg_alias', "The alias of the package to create release on.");
        $help->addParam('version', 'Version number of the package.  If not specified, you will be prompted for one.');
        $help->addFlag('--breaking', 'Optional, and if present this release will be marked as including breaking changes.');
        $help->addFlag('-m', 'Optional commit message.');
        $help->addFlag('--file', 'Optional location of file containing commit message.');

        // Return
        return $help;
    }

}

