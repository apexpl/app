<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

use Apex\Armor\SshKeys\PrivateToSshPublic;
use OpenSSLAsymmetricKey;

/**
 * RSA Key
 */
class RsaKey
{

    /**
     * Constructor
     */
    public function __construct(
        private ?string $alias = null, 
        private ?OpenSSLAsymmetricKey $privkey = null, 
        private ?string $password = null, 
        private ?string $public_key = null, 
        private ?string $private_key = null, 
        private ?string $ssh_pubkey = null
    ) { 

    }

    /**
     * Get alias
     */
    public function getAlias():?string
    {
        return $this->alias;
    }

    /**
     * Get public key
     */
    public function getPublicKey():?string
    {

        // Get public key, if needed
        if ($this->public_key === null && $this->privkey !== null) { 
            $details = openssl_pkey_get_details($this->privkey);
            $this->public_key = $details['key'];
        }
        return $this->public_key;
    }

    /**
     * Get private key
     */
    public function getPrivateKey():?string
    {

        // Export key, if needed
        if ($this->private_key === null && $this->privkey !== null) { 
            openssl_pkey_export($this->privkey, $privkey_out, $this->password);
            $this->private_key = $privkey_out;
        }
        return $this->private_key;
    }

    /**
     * Get public SSH key
     */
    public function getPublicSshKey():?string
    {

        // Encode SSH key, if eeded
        if ($this->ssh_pubkey === null && $this->privkey !== null) { 
            $this->ssh_pubkey = PrivateToSshPublic::get($this->privkey);
        }
        return $this->ssh_pubkey;
    }

    /**
     * Get SHA256 hash
     */
    public function getSha256():?string
    {
        $parts = explode(' ', $this->getPublicSshKey());
        $hash = hash('sha256', base64_decode($parts[1]), true);
        return preg_replace("/=*$/", "", base64_encode($hash));
    }

    /**
     * Get password
     */
    public function getPassword():?string
    {
        return $this->password;
    }

    /**
     * Get loaded privkey
     */
    public function getPrivkey():?OpenSSLAsymmetricKey
    {
        return $this->privkey;
    }

    /**
     * Set alias
     */
    public function setAlias(string $alias):void
    {
        $this->alias = $alias;
    }

    /**
     * Set privkey
     */
    public function setPrivkey(OpenSSLAsymmetricKey $key):void
    {
        $this->privkey = $key;
    }

    /**
     * Set password
     */
    public function setPassword(?string $password):void
    {
        $this->password = $password;
    }

}


