<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

use Apex\App\Network\Models\RsaKey;
use Apex\Armor\x509\DistinguishedName;

/**
 * x509 Certificate
 */
class Certificate
{

    /**
     * Constructor
     */
    public function __construct(
        private ?string $crt = null, 
        private ?string $csr = null, 
        private ?RsaKey $rsa_key = null, 
        private ?DistinguishedName $dn = null
    ) { 

    }

    /**
     * Get CRT
     */
    public function getCrt():?string
    {
        return $this->crt;
    }

    /**
     * Get CSR
     */
    public function getCsr():?string
    {
        return $this->csr;
    }

    /**
     * Get RSA key
     */
    public function getRsaKey():?RsaKey
    {
        return $this->rsa_key;
    }

    /**
     * Get distinguished name
     */
    public function getDistinguishedName():?DistinguishedName
    {
        return $this->dn;
    }

    /**
     * Get fingerprint
     */
    public function getFingerprint():string
    {
        $crt = openssl_x509_read($this->crt);
        $fingerprint = openssl_x509_fingerprint($crt, 'sha384');
        return implode(':', str_split($fingerprint, 4));
    }

    /**
     * Get subject
     */
    public function getIssuedTo():array
    {

        // Parse crt
        $info = openssl_x509_parse($this->crt);
        $sub = $info['subject'];

        // Set issued to
        $issued_to = [
            "$sub[O] ($sub[OU])",
            "$sub[L], $sub[ST], $sub[C]",
            $sub['emailAddress']
        ];

        // Return
        return $issued_to;
    }

    /**
     * Set crt
     */
    public function setCrt(string $crt):void
    {
        $this->crt = $crt;
    }


}


