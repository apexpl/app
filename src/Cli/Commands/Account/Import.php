<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Account;

use Apex\Svc\{Db, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{CertificateHelper, NetworkHelper};
use Apex\App\Network\Stores\{AccountsStore, RsaKeyStore};
use Apex\App\Network\Models\{LocalRepo, Certificate, LocalAccount, RsaKey};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Import existing account
 */
class Import implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(CertificateHelper::class)]
    private CertificateHelper $crt_helper;

    #[Inject(AccountsStore::class)]
    private AccountsStore $acct_store;

    #[Inject(NetworkHelper::class)]
    private NetworkHelper $network_helper;

    #[Inject(RsaKeyStore::class)]
    private RsaKeyStore $rsa_store;

    // Properties
    private string $username;
    private string $password;
    private LocalRepo $repo;
    private bool $has_crt = false;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['repo']);
        $repo_alias = $opt['repo'] ?? 'apex';

        // Get repo
        if (!$this->repo = $this->network_helper->getRepo()) { 
            $cli->error("Unable to determine repository to import account into");
            return;
        }

        // Get login credentials
        $this->username = $cli->getInput('Account Username: ');
        $this->password = $cli->getInput('Account Password: ', '', true);

        // Check account
        if (!$res = $this->network->post($this->repo, 'users/check', ['username' => $this->username, 'password' => $this->password])) { 
            $cli->error("Invalid username or password specified.  Please try again.");
            return;
        }
        $this->email = $res['email'] ?? '';

        // Initial checks
        $auth_ok = $res['auth_ok'] ?? false;
        if ($auth_ok !== true) { 
            $cli->error("There was a problem authenticating your account.  If you believe this is in error, please contact customer support.");
            return;
        }

        // Process as needed
        if ($res['crt_count'] == 0) { 
            $this->generateCertificate($cli, (int) $res['ssh_count']);
        } else { 
            $this->importCertificate($cli, $res['ssh_hashes']);
        }

    }

    /**
     * Import CRt
     */
    private function importCertificate(Cli $cli, array $allowed_hashes = []):void
    {

        // Send header
        $cli->sendHeader('Signing Certificate Found');
        $cli->send("Your account has been successfully located, and currently has a signing certificate registered to it.  If you have the private key of this certificate, please enter the path to the key file below.  Otherwise, if you have lost the private key please login to your account via the web site to reset your keys.\r\n\r\n");

        // Get .pem file
        if (!$rsa = $this->requestKeyFile($cli, 'PEM')) { 
            return;
        }

        // Save the key
        if (!$this->rsa_store->save($rsa->getAlias(), $rsa)) { 
            $cli->error("Unable to save key, as a key with the alias '" . $rsa->getAlias() . "' already exists on this machine.");
            return;
        }

        // Check SSH key
        $ssh_alias = in_array($rsa->getSha256(), $allowed_hashes) ? $rsa->getAlias() : null;
        if ($ssh_alias === null) { 
            $hashes = $this->rsa_store->getSha256Hashes();
            foreach ($hashes as $chk_alias => $hash) { 

                if (in_array($hash, $allowed_hashes)) { 
                    $ssh_alias = $chk_alias;
                    break;
                }
            }
        }

        // Get ssh key, if needed
        if ($ssh_alias === null && !$ssh = $this->requestKeyFile($cli, 'SSH Private')) { 
            return;
        } elseif ($ssh_alias === null) { 
            $ssh_alias = $ssh->getAlias();
        }

        // Create account instance
        $acct = $this->cntr->make(LocalAccount::class, [
            'username' => $this->username,
            'email' => $this->email,
            'repo_alias' => $this->repo->getAlias(),
            'sign_key' => $rsa->getAlias(),
            'ssh_key' => $ssh_alias
        ]);

        // Save account
        $this->acct_store->save($acct);

        // Send message
        $cli->send("Successfully imported the account $this->username with e-mail address $this->email, and you may begin using it as normal.\r\n\r\n");
    }

    /**
     * Request key file
     */
    private function requestKeyFile(Cli $cli, string $type):?RsaKey
    {

        $pem_file = $cli->getInput("Location of " . $this->username . "'s $type File: ");
        if (!file_exists($pem_file)) { 
            $cli->error("No file exists at, $pem_file");
            return null;
        }
        $private_key = file_get_contents($pem_file);

        // Get alias to save key as
        $alias = $cli->getInput('Alias to save key as [default]: ', 'default');
        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, can not contain spaces or special characters.");
            return null;
        }

        // Unlock private key
        $password = null;
        if (!$privkey = openssl_pkey_get_private($private_key)) { 

            do { 
                $password = $cli->getInput('Private Key Password: ', '', true);
                if (!$privkey = openssl_pkey_get_private($private_key, $password)) { 
                    $cli->send("\r\nInvalid password, please try again.\r\n\r\n");
                    continue;
                }
                break;
            } while (true);
        }

        // Create RSA key
        $rsa = $this->cntr->make(RsaKey::class, [
            'alias' => $alias,
            'privkey' => $privkey,
            'password' => $password,
            'private_key' => $private_key
        ]);

        // Return
        return $rsa;
    }


    /**
     * Generate crt
     */
    private function generateCertificate(Cli $cli, int $ssh_count)
    {

        // Ask if want to generate crt
        $cli->send('Signing Certificate Not Found');
        $cli->send("Your account has been successfully located, but does not currently have a signing certificate registered to it.\r\n\rn");
            if (!$cli->getConfirm('Would you like to generate a certificate now?', 'y')) { 
            return;
        }

        // Generate
        $common_name = $this->username . '@' . $this->repo->getAlias();
        if (!$cert = $this->crt_helper->generate($common_name)) { 
            return null;
        }

        // Ask to use SSH key
        $use_as_ssh = 0;
        if ($ssh_count == 0 && $cli->getConfirm('Would you also like to use this new private key as your SSH key to communicate with the repository?', 'y') === true) { 
            $use_as_ssh = 1;
        }

        // Send API request
        $res = $this->network->post($this->repo, 'users/add_certificate', [
            'username' => $this->username, 
            'password' => $this->password, 
            'csr' => $cert->getCsr(), 
            'public_key' => $cert->getRsaKey()->getPublicKey(),
            'ssh_key' => $cert->getRsaKey()->getPublicSshKey(), 
            'use_as_ssh' => $use_as_ssh
        ]);

        // Save user account
        $cert->setCrt($res['crt']);
        $this->acct_store->create($this->username, $this->email, $this->repo, $cert, $cert->getRsaKey());

        // Success message
        $cli->send("Thank you, and your account has been successfully imported.  You may now continue using Apex as normal, and have regained full access to your repositories.\r\n\r\n");

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Create help
        $help = new CliHelpScreen(
            title: 'Import Account',
            usage: 'account import',
            description: "Imports an existing Apex account onto the local machine for use.  Used when you have installed Apex on a new machine, and wish to use your existing Apex account to publish commiets and releases to your repositories.",
            example: [
                './apex account import'
            ]
        );

        // Return
        return $help;
    }

}

