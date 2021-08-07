<?php
declare(strict_types = 1);

namespace Apex\App\Network\ApiClient;

use Apex\App\Network\NetworkClient;
use Apex\App\Network\Models\{LocalRepo, Certificate, RsaKey};

/**
 * Account registration
 */
class RegisterAccount
{

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Perform
     */
    public function process(
        LocalRepo $repo, 
        string $username, 
        string $password, 
        string $email, 
        Certificate $csr, 
        RsaKey $ssh_key
    ):string { 

        // Set vars
        $request = [
            'username' => $username, 
            'password' => $password, 
            'email' => $email,
            'pubkey' => $csr->getRsaKey()->getPublicKey(), 
            'csr' => $csr->getCsr(), 
            'ssh_pubkey' => $ssh_key->getPublicSshKey()
        ];

        // Send request
        $res = $this->network->post($repo, 'users/register', $request);

        // Return crt
        return $res['crt'];
    }

}

