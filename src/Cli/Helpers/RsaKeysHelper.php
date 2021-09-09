<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\Container;
use Apex\App\Cli\Cli;
use Apex\App\Network\Stores\RsaKeyStore;
use Apex\App\Network\Models\RsaKey;

/**
 * RSA / SSH Keys
 */
class RsaKeysHelper
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(RsaKeyStore::class)]
    private RsaKeyStore $store;

    /**
     * Get SSH key
     */
    public function get(bool $is_ssh = false, string $username = 'default'):RsaKey
    {

        // Get keys
        $keys = $this->store->list();
        if (count($keys) > 0) { 
            $this->cli->send("The following private keys have been found:\r\n\r\n");
            foreach ($keys as $alias => $name) { 
                $this->cli->send("    [$alias] $name\r\n");
            }
            $this->cli->send("If you wish to use one of the below keys, please enter its name.  Otherwise, leave the field blank and press Enter to generate a new key.\r\n\r\n");

            // Get alias
            $alias = $this->cli->getInput("Key Alias []: ");
            if ($alias != '' && $this->store->has($alias) === true) { 
                $rsa = $this->store->get($alias);
                if ($is_ssh === true) { 
                    $this->store->addSshAgent($alias);
                }
                return $rsa;
            }
        }

        // Get new key
        $rsa = $this->generate($username);
        if ($is_ssh === true) { 
            $this->store->addSshAgent($rsa->getAlias());
        }
        return $rsa;
    }

    /**
     * Generate new SSH key
     */
    public function generate(string $username = 'default'):RsaKey
    {

        // Get password
        $this->cli->send("A new 4096 bit RSA key will now be generated, and please enter the desired password below.  You may leave this blank and press Enter twice to create the private key without a password.\r\n\r\n");
        $password = $this->cli->getNewPassword('Private Key Password', true);
        $this->cli->send("\r\n");

        // Generate private key
        $privkey = openssl_pkey_new([
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA 
        ]);

        // Get new RSA key
        $rsa = $this->cntr->make(RsaKey::class, [
            'privkey' => $privkey, 
            'password' => $password
        ]);

        // Save key
    do { 
            $alias = strtolower($this->cli->getInput("Enter alias you wish to save this key as [$username]: ", $username));
            if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
                $this->cli->send("Invalid alias, can not contain spaces or special characters.  Please try again.\r\n\r\n");
                continue;
            } elseif ($this->store->has($alias) === true) { 
                $this->cli->send("A key with the alias '$alias' already exists.  Please try again.\r\n\r\n");
                continue;
            }

            // Save
            $this->store->save($alias, $rsa);
            $this->cli->send("\r\nSaved the private key with alias, $alias\r\n\r\n");
            break;
        } while (true);

        // Return
        $rsa->setAlias($alias);
        return $rsa;
    }

}

