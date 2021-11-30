<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Branch;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * List branches
 */
class Ls implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');

        // Check for project
        if (($info = $this->redis->hgetall('config:project')) && !$pkg = $this->pkg_store->get($pkg_alias)) {
            $pkg_alias = $info['pkg_alias'];
        }


        // Load package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif (($svn = $pkg->getSvnRepo()) === null) { 
            $cli->error("This package has not yet been assigned to a repository.  Please first commit the package, see 'apex help package commit' for details.");
            return;
        }

        // Get branches
        $svn->setTarget('branches');
        $lines = explode("\n", $svn->exec(['list', '-v']));

        // Get branches
        $branches = [['Name', 'Author', 'Last Updated', 'Rev#']];
        foreach ($lines as $line) { 

            // Parse line
            if (!preg_match("/^(\d+?)\s+(\w+?)\s+(.+)\s(.+)$/", trim($line), $m)) { 
                continue;
            }
            list($full, $rev_id, $user, $date, $file) = $m;

            // Add to branches
            if ($file != './') { 
                $file = preg_replace("/\/$/", "", $file);
                $branches[] = [$file, $user, $date, $rev_id];
            }
        }

        // Display table
        $cli->sendTable($branches);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Branches',
            usage: 'branch list <PKG_ALIAS>',
            description: 'List all branches on a package.'
        );

        $help->addParam('pkg_alias', 'The package alias to list branches for.');
        $help->addExample('./apex branch list my-shop');

        // Return
        return $help;
    }

}


