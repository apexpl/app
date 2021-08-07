<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\App\Cli\Cli;
use Apex\App\Network\Models\RsaKey;
use OpenSSLAsymmetricKey;

/**
 * Abstract signer
     */
class AbstractSigner
{

    #[Inject(Cli::class)]
    protected Cli $cli;

    /**
     * Unlock private key
     */
    protected function unlockPrivateKey(RsaKey $rsa):OpenSSLAsymmetricKey
    {

        // Initialize
        $private_key = $rsa->getPrivateKey();

        // Unlock key
        if (!$privkey = openssl_pkey_get_private($private_key)) { 



            if (!$password = $this->cli->getSigningPassword()) { 
                $password = $this->cli->getInput($rsa->getAlias() . "'s Signing Password: ", '', true);
            }

            // Loop until we have correct password or user exits
            do {
                if (!$privkey = openssl_pkey_get_private($private_key, $password)) { 
                    $this->cli->send("Invalid password, please try again.\r\n\r\n");
                    $password = $this->cli->getInput($rsa->getAlias() . "'s Signing Password: ", '', true);
                    continue;
                }
                $rsa->setPassword($password);
            $this->cli->setSigningPassword($password);
                break;
            } while (True);
        }

        // Return
        return $privkey;
    }


}

