<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Release;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Svn\SvnChangelog;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Change log
 */
class ChangeLog implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(SvnChangelog::class)]
    private SvnChangelog $svn_changelog;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();

        // Initialize
        $old_version = $args[1] ?? '';
        $new_version = $args[2] ?? 'latest';

        // Check
        if ($old_version == '') { 
            $cli->error("You did not specify a version to check from.");
            return;
        } elseif (null === ($svn = $pkg->getSvnRepo())) { 
            $cli->error("The package '$pkg_alias' is not yet assigned to a repository.  Please commit the package first, see 'apex help package commit' for details.");
            return;
        }

        // Get change log
        $log = $this->svn_changelog->get($svn, $old_version, $new_version);

        // Print result
        $cli->sendHeader("Changelog for $pkg_alias v$old_version - $new_version");
        $cli->send("Added / Modified:\r\n\r\n");
        foreach ($log['updated'] as $file) { 
            $cli->send("    $file\r\n");
        }

        $cli->send("\r\nDeleted:\r\n\r\n");
        foreach ($log['deleted'] as $file) { 
            $cli->send("    $file\r\n");
        }
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'View Changelog',
            usage: 'release changelog <PKG_ALIAS> <OLD_VERSION> [<NEW_VERSION>]',
            description: 'Displays a list of files modified and deleted between two different releases of a package.'
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to display changelog of.');
        $help->addParam('old_version', 'The version of the release to view log starting from.');
        $help->addParam('new_version', 'The version of the release to view log up to.  Defaults to the latest release if not specified.');
        $help->addExample('./apex release changelog myshop 0.2.6');

        // Return
        return $help;
    }

}


