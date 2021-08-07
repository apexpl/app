<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, PackageHelper};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Rollback
 */
class Update implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }

        // Get account
        if (!$acct = $pkg->getLocalAccount()) { 
            $acct = $this->acct_helper->get();
        }

        // Send api call
        $this->network->setAuth($acct);
        $res = $this->network->post($pkg->getRepo(), 'repos/update', $pkg->getJsonRequest());

        // Send message
        $cli->send("Successfully updated general details of the package, " . $pkg->getSerial() . "\r\n\r\n");

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Update General Package Details',
            usage: 'package update <PKG_ALIAS>',
            description: 'Updates the package within the repository with any general information changed in the package.yml configuration file, such as name, access, price, et al.'
        );
        $help->addParam('pkg_alias', 'The alias of the package to update.');
        $help->addExample('./apex package update my-shop');

        // Return
        return $help;
    }

}

