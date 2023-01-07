<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalAccount;
use Apex\App\Network\Stores\RsaKeyStore;
use Apex\App\Exceptions\ApexRsaKeyNotExistsException;
use Apex\App\Attr\Inject;

/**
 * Api Auth
 */
class ApiAuth extends AbstractSigner
{

    #[Inject(RsaKeyStore::class)]
    private RsaKeyStore $store;

    /**
     * Create signature
     */
    public function sign(LocalAccount $account, string $challenge):string
    {

        // Get RSA key
        if (!$rsa = $this->store->get($account->getSignKey())) { 
            throw new ApexRsaKeyNotExistsException("Unable to find the RSA key, " . $account->getSignKey());
        }

        // Unlock key
        $privkey = $this->unlockPrivateKey($rsa);
        $rsa->setPrivkey($privkey);

        // Sign challenge
        openssl_sign($challenge, $signature, $privkey, 'sha384');
        $signature = bin2hex($signature);

        // Return
        return $signature;
    }

}


