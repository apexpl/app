<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Image;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * List installation images
 */
class Ls implements CliCommandInterface
{

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get account
        $acct = $this->acct_helper->get();

        // Get repo
        $repo = $this->repo_store->get('apex');

        // Send http request
        $this->network->setAuth($acct);
        $res = $this->network->get($repo, 'images/ls');

        // Go through images
        $images = [['Name', 'Version', 'Downloads', 'Description']];
        foreach ($res as $vars) { 
            $images[] = [$vars['name'], (string) $vars['version'], (string) $vars['downloads'], $vars['description']];
        }

        // Display table
        $cli->sendTable($images);
    }

    /**
     8 Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Installation Images',
            usage: 'image list',
            description: 'List all installation images you have published to the repository.'
        );

        $help->addExample('./apex image list');
        return $help;
    }

}




