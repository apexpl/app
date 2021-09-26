<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, AccountHelper};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Scan
 */
class Scan implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Checks
        if (!$info = $this->redis->hgetall('config:project')) {
            $cli->error("There is no project checked out on this system.");
            return;
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) {
            return;
        }
        $pkg_alias = $pkg->getAlias();

        // Get account
        $acct = $this->acct_helper->get();

        // Send api call
        $this->network->setAuth($acct);
        $res = $this->network->post($pkg->getRepo(), 'stage/scan', ['pkg_alias' => $pkg_alias]);

        // Send message
        $cli->send("Successfully scanned the package '$pkg_alias' within the staging environment.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    { 

        $help = new CliHelpScreen(
            title: 'Scan Package ono Staging Environment',
            usage: 'project scan <PKG_ALIAS>',
            description: 'Scans a package that resides on the staging environment of the project.'
        );

        $help->addParam('pkg_alias', 'The alias of the package to scan.');
        $help->addExample('./apex project scan my-shop');

        // return
        return $help;
    }

}


