<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\Container;
use Apex\App\Network\Stores\CertificateStore;
use Apex\App\Network\Models\{LocalAccount, Certificate};
use Apex\App\Network\Sign\AbstractSigner;
use Apex\Armor\x509\DistinguishedName;
use Apex\App\Exceptions\ApexCertificateNotExistsException;
use Apex\App\Attr\Inject;

/**
 * ACL Helper
 */
class AclHelper extends AbstractSigner
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(CertificateStore::class)]
    private CertificateStore $cert_store;

    /**
     * Get csr request
     */
    public function getRequestCsr(LocalAccount $acct, string $common_name):?Certificate
    {

        // Check if cert already exists
        $crt_name = str_replace('@', '.', $common_name);
        try {
            $crt =$this->cert_store->get($crt_name);
            return null;
        } catch (ApexCertificateNotExistsException $e) {
        }

        // Get account certificate
        $cert = $acct->getCertificate();
        $rsa = $cert->getRsaKey();

        // Read certificate
        $info = openssl_x509_parse($cert->getCrt());
        $sub = $info['subject'];

        // Get distinguished name
        $dn = new DistinguishedName(
            country: $sub['C'],
            province: $sub['ST'],
            locality: $sub['L'],
            org_name: $sub['O'],
            org_unit: $sub['OU'],
            common_name: $common_name,
            email: $sub['emailAddress']
        );

        // Generate csr
        $privkey = $this->unlockPrivateKey($rsa);
        $csr = openssl_csr_new($dn->toArray(), $privkey, ['digest_alg' => 'sha384']);
        openssl_csr_export($csr, $csr_out);

        $csr = $this->cntr->make(Certificate::class, [
            'csr' => $csr_out, 
            'rsa_key' => $rsa, 
            'dn' => $dn
        ]);

        // Return
        return $csr;
    }

}



