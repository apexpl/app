<?php
declare(strict_types = 1);

namespace Apex\App\Network;

use Apex\Svc\HttpClient;
use Apex\App\Cli\CLi;
use Apex\App\Network\Models\{LocalRepo, LocalAccount};
use Apex\App\Network\Sign\ApiAuth;
use Nyholm\Psr7\Request;
use Apex\App\Exceptions\ApexApiClientException;
use Apex\App\Attr\Inject;

/**
 * Network client
 */
class NetworkClient
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

    #[Inject(ApiAuth::class)]
    private ApiAuth $api_auth;

    // Properties
    private bool $has_auth = false;
    private ?LocalAccount $account = null;
    private ?string $signature = null;

    /**
     * Post
     */
    public function post(LocalRepo $repo, string $path, array $request):array
    {

        // Send request
        $res = $this->send($repo, $path, 'POST', [], $request);

        // Return
        return $res;
    }

    /**
     * Get
     */
    public function get(LocalRepo $repo, string $path):array
    {

        // Send request
        $res = $this->send($repo, $path, 'GET');

        // Return
        return $res;
    }

    /**
     * Authenticate
     */
    private function authenticate(LocalRepo $repo)
    {

        // Get auth challenge
        $res = $this->send($repo, 'get_auth_challenge', 'POST', [], ['username' => $this->account->getUsername()]);

        // Create signature
        $this->signature = $this->api_auth->sign($this->account, $res['challenge']);
    }

    /**
     * Send request
     */
    private function send(LocalRepo $repo, string $path, string $method = 'GET', array $headers = [], array $request = [])
    {

        // Authenticate, if needed
        if ($this->has_auth === true && $path != 'get_auth_challenge') { 

            if ($this->signature === null) { 
                $this->authenticate($repo);
            }
            $headers['API-Username'] = $this->account->getUsername();
            $headers['API-Signature'] = $this->signature;
        }

        // Get content type, if POST
        $method = strtoupper($method);
        if ($method == 'POST') {
            $headers['Content-type'] = 'application/x-www-form-urlencoded';
        }

        // Create request
        $url = $repo->getApiUrl($path);
        $req = new Request($method, $url, $headers, http_build_query($request));

        // Send request
        try {
            $res = $this->http->sendRequest($req);
        } catch (Exception $e) { 
            throw new ApexApiClientException($e->getMessage);
        }

        // Decode response
        if (!$json = json_decode($res->getBody()->getContents(), true)) { 
            throw new ApexApiClientException("Did not receive valid JSON object from repository, got instead: " . $res->getBody());
        } elseif ($res->getStatusCode() != 200) {
            $file = $json['data']['file'] ?? '';
            $line = $json['data']['line'] ?? 0;
            throw new ApexApiClientException("Received a " . $res->getStatusCode() . " from API with message, $json[message] at file $file on line $line");
        }

        // Return
        return $json['data'];
    }

    /**
     * Set auth
     */
    public function setAuth(LocalAccount $account):void
    {
        $this->has_auth = true;
        $this->account = $account;
        $this->signature = null;
    }

    /**
     * Reset auth
     */
    public function resetAuth():void
    {
        $this->signature = null;
    }

}


