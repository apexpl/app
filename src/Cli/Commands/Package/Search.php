<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Install package
 */
class Search implements CliCommandInterface
{

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $term = $args[0] ?? '';
        $results = [['Name', 'Repo', 'Description']];

        // Go through repos
        foreach ($this->repo_store->list() as $repo_alias => $vars) { 
            $repo = $this->repo_store->get($repo_alias);
            $res = $this->network->post($repo, 'repos/search', ['pkg_alias' => $term]);
            if ($res['count'] == 0) { 
                continue;
            }

            foreach ($res['packages'] as $vars) {
                $results[] = [
                    $vars['serial'] . ' v' . $vars['version'],
                    $repo_alias,
                    $vars['description']
                ];
            }
        }

        // No packages found
        if (count($results) == 0) {
            $cli->send("No packages were found that match the term '$term'\r\n\r\n");
        } else {
            $cli->send("The following packages were found for the term '$term'\n\n");
            $cli->sendTable($results);
            $cli->send("You may install any of these packages by running the command:\n\n");
            $cli->send("    apex install <PACKAGE_NAME>\n\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Search Packages',
            usage: 'package search <TERM>',
            description: 'Search all configured repos for packages for the specified term.'
        );

        $help->addParam('term', 'The term to search packages for');
        $help->addExample('./apex package search ecommerce');

        // Return
        return $help;
    }

}


