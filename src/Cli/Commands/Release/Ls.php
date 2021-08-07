<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Release;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * List releases
 */
class Ls implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');

        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        }

        // Get branches
        $svn->setTarget('tags');
        $lines = explode("\n", $svn->exec(['list', '-v']));

        // Get tags
        $tags = [];
        foreach ($lines as $line) { 

            // Parse line
            if (!preg_match("/^(\d+?)\s+(\w+?)\s+(.+)\s(.+)$/", trim($line), $m)) { 
                continue;
            }
            list($full, $rev_id, $user, $date, $file) = $m;

            // Add to branches
            if ($file != './') { 
                $file = 'v' . preg_replace("/\/$/", "", $file);
                $tags[] = [$file, $user, $date, $rev_id];
            }
        }

        // Sort and finish tags
        usort($tags, function ($a, $b) { return version_compare($a[0], $b[0], '>') ? 1 : -1; });
        array_unshift($tags, ['Version', 'Author', 'Release Date', 'Rev#']);

        // Display table
        $cli->sendTable($tags);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Releases',
            usage: 'release list <PKG_ALIAS>',
            description: 'List all releases on a package.'
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to list releases for.');
        $help->addExample('./apex release list my-shop');

        // Return
        return $help;
    }

}


