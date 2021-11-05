<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\App\Cli\Cli;
use Apex\App\Cli\Helpers\{DistinguishedNamesHelper, RsaKeysHelper};
use Apex\App\Network\Models\{Repo, Certificate};
use Apex\App\Attr\Inject;

/**
 * Certificate helper
 */
class CertificateHelper
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(DistinguishedNamesHelper::class)]
    private DistinguishedNamesHelper $dn_helper;

    #[Inject(RsaKeysHelper::class)]
    private RsaKeysHelper $rsa_helper;

    /**
     * Generate CSR
     */
    public function generate(string $common_name, string $username = 'default'):?Certificate
    {

        // Send header
        $this->cli->sendHeader('Signing Certificate');
        $this->cli->send("For security and confidence, Apex requires all commits and releases to be digitally signed, and a new signing certificate will now be generated.\r\n\r\n");

        // Get distinguished name
        if (!$dn = $this->dn_helper->get()) { 
            return null;
        }
        $dn->common_name = $common_name;

        // Get RSA key
        $this->cli->sendHeader('Signing Password');
        $rsa = $this->rsa_helper->get(false, $username);

        // Set signing password, if needed
        if ($rsa->getPassword() !== null) { 
            $this->cli->setSigningPassword($rsa->getPassword());
        }

        // Generate CSR
        $privkey = $rsa->getPrivkey();
        $csr = openssl_csr_new($dn->toArray(), $privkey, ['digest_alg' => 'sha384']);
        openssl_csr_export($csr, $csr_out);

        // Get new certificate
        $cert = new Certificate(
            csr: $csr_out, 
            rsa_key: $rsa, 
            dn: $dn
        );

        // Return
        return $cert;
    }

}

