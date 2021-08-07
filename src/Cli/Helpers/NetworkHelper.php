<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalRepo;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Exceptions\ApexConfigException;

/**
 * Network CLI helper
 */
class NetworkHelper
{

    #[Inject(ReposStore::class)]
    private ReposStore $store;

    #[Inject(Cli::class)]
    private Cli $cli;

    // Properties
    private ?LocalAccount $account = null;

    /**
     * Get repository
     */
    public function getRepo(string $message = ''):LocalRepo
    {

        // Get all repos
        $repos = $this->store->list();
        if (count($repos) == 0) { 
            throw new ApexConfigException("There are no repositories configured on this system.");
        } elseif (count($repos) == 1 && $repo = $this->store->get(array_keys($repos)[0])) { 
            return $repo;
        }

        // Create repo options
        $options = [];
        foreach ($repos as $alias => $vars) { 
            $name = $alias . ' (https://' . $vars['http_host'] . ')';
            $options[$alias] = $name;
        }

        // Get option
        $repo_alias = $this->cli->getOption("To continue, select a repository to use for this action: ", $options, '', true);

        // Get repo and return
        $repo = $this->store->get($repo_alias);
        return $repo;
    }

}


