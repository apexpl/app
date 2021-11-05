<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, NetworkHelper};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Register account
 */
class Register implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $helper;

    #[Inject(NetworkHelper::class)]
    private NetworkHelper $network_helper;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get repo
        $repo = $this->network_helper->getRepo();

        // Register account
        $this->helper->register($repo);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Register Account', 
            usage: 'account register', 
            description: 'Registers a new account with Apex, allowing you to create and commit to your own repositories at https://code.apexpl.io/<username>/, contribute to other repositories, access private / commercial packages, and more.',
            examples: [
                './apex account register'
            ]
        );

        // Return
        return $help;
    }

}

