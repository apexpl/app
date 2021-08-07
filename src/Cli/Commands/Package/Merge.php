<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Merge
 */
class Merge implements CliCommandInterface
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
        $branch_name = $this->convert->case(($args[1] ?? ''), 'lower');

        // Get package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("package does not exist, $pkg_alias");
            return;
        } elseif ($pkg->isVersionControlled() !== true) { 
            $cli->error("The package '$pkg_alias' is not currently version controlled.  Please checkout the package first, see 'apex help package checkout' for details.");
            return;
        }
        $svn = $pkg->getSvnRepo();

        $url = $svn->getSvnUrl('branches/' . $branch_name, false);

        $svn->setTarget('', 0, true);
        $res = $svn->exec(['merge'], [$url]);
        echo "RES: $res\n";





    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

    }

}



