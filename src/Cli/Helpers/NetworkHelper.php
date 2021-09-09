<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\{HttpClient, Container};
use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalRepo;
use Apex\App\Network\Stores\ReposStore;
use Nyholm\Psr7\Request;
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

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

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

    /**
     * Add repo
     */
    public function addRepo(string $host):bool
    {

        // Set request
        $url = 'https://' . $host . '/api/enduro/info';
        $req = new Request('GET', $url);

        // Send http request
        $res = $this->http->sendRequest($req);
        $status = $res->getStatusCode();

        // Check status
        if ($status != 200) { 
            $this->cli->error("No repository exists at the host, $host");
            return false;
        } elseif (!$json = json_decode($res->getBody()->getContents(), true)) { 
            $cli->send("Did not receive a valid JSON response from the host, $host.  Instead, received: " . $res->getBody());
            return false;
        } elseif (!isset($json['data']['http_host'])) { 
            $cli->error("Did not receive a valid JSON response from the server.");
            return false;
        }
        $json = $json['data'];

        // Create repo
        $repo = $this->cntr->make(LocalRepo::class, [
            'host' => $host,
            'http_host' => $json['http_host'],
            'svn_host' => $json['svn_host'],
            'staging_host' => $json['staging_host'],
            'alias' => $json['alias'],
            'name' => $json['name']
        ]);

        // Save repo
        $this->store->save($repo);

        // Return
        return true;
    }

}


