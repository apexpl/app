<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Update
 */
class Pull implements CliCommandInterface
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

        // Initialize
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        if (($info = $this->redis->hgetall('config:project')) && !$pkg = $this->pkg_store->get($pkg_alias)) {
            $pkg_alias = $info['pkg_alias'];
        }

        // Get package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("package does not exist, $pkg_alias");
            return;
        } elseif ($pkg->isVersionControlled() !== true) { 
            $cli->error("The package '$pkg_alias' is not currently version controlled.  Please checkout the package instead, see 'apex help package checkout' for details.");
            return;
        }

        // Update
        $svn = $pkg->getSvnRepo();
        $svn->setTarget('', 0, true);
        if (!$svn->exec(['update'], [], true)) {
            $svn->checkSshAgent();
            $svn->exec(['update'], [], true);
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Update Package',
            usage: 'update <PKG_ALIAS>',
            description: 'Updates the local working copy with the latest files on the repository.'
        );

        // Add params
        $help->addParam('pkg_alias', "The package alias to update.");
        $help->addExample('./apex package update my-shop');

        // Return
        return $help;
    }

}


