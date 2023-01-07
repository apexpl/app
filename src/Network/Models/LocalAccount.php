<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

use Apex\App\Network\Models\Certificate;
use Apex\App\Network\Stores\{ReposStore, CertificateStore, RsaKeyStore};
use Apex\App\Attr\Inject;

/**
 * Accounts
 */
class LocalAccount
{

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(CertificateStore::class)]
    private CertificateStore $cert_store;

    #[Inject(RsaKeyStore::class)]
    private RsaKeyStore $rsa_store;



    /**
     * Constructor
     */
    public function __construct(
        private string $username = '', 
        private string $email = '',  
        private string $password = '',  
        private string $repo_alias = '', 
        private string $ssh_key = '', 
            private string $sign_key = ''
    ) {

    }
    /**
     * Get username
     */
    public function getUsername():string
    {
        return $this->username;
    }

    /**
     * Get e-mail
     */
    public function getEmail():string
    {
        return $this->email;
    }

    /**
     * Get password
     */
    public function getPassword():string
    {
        return $this->password;
    }
        /**
     * Get repo alias
     */
    public function getRepoAlias():string
    {
        return $this->repo_alias;
    }

    /**
     * Get repo
     */
    public function getRepo():LocalRepo
    {
        return $this->repo_store->get($this->repo_alias);
    }

    /**
     * Get ssh key
     */
    public function getSshKey():string
    {
        return $this->ssh_key;
    }

    /**
     * Get sign key
     */
    public function getSignKey():string
    {
        return $this->sign_key;
    }

    /**
     * Get certificate
     */
    public function getCertificate():?Certificate
    {

        // Load cert
        $crt_alias = $this->username . '.' . $this->repo_alias;
        if (!$crt = $this->cert_store->get($crt_alias)) { 
            return null;
        }

        // Get rsa key
        if (!$rsa = $this->rsa_store->get($this->sign_key)) { 
            return null;
        }

        // Make cert
        $cert = new Certificate(
            crt: $crt,
            rsa_key: $rsa
        );

        // Return
        return $cert;
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        // Set vars
        $vars = [
            'username' => $this->username,
            'email' => $this->email,
            'repo_alias' => $this->repo_alias,
            'sign_key' => $this->sign_key,
            'ssh_key' => $this->ssh_key
        ];

        // Return
        return $vars;
    }

}

