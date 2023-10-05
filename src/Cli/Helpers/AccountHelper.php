<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\{Container, Debugger};
use Apex\App\Cli\Cli;
use Apex\App\Cli\Helpers\NetworkHelper;
use Apex\App\Network\Models\{LocalRepo, LocalAccount};
use Apex\App\Cli\Helpers\{RsaKeysHelper, CertificateHelper};
use Apex\App\Network\Stores\{AccountsStore, ReposStore};
use Apex\App\Network\Sign\VerifyCertificate;
use Apex\App\Network\ApiClient\RegisterAccount;
use Apex\App\Network\NetworkClient;
use Apex\App\Attr\Inject;

/**
 * Account utils
 */
class AccountHelper
{

    /**
     * Constructor
     */
    public function __construct( 
        private Cli $cli,
        protected Container $cntr,
        private AccountsStore $store,
        private CertificateHelper $certs, 
        private RsaKeysHelper $rsa_keys, 
        private RegisterAccount $register,
        private VerifyCertificate $verify_cert,
        private ReposStore $repo_store,
        private NetworkClient $network,
        private NetworkHelper $network_helper,
        private ?Debugger $debugger = null
    ) { 

    }

    /**
     * Get an account
     */
    public function get():LocalAccount
    {

        // Check if we have account already
        if ($this->cntr->has(LocalAccount::class)) {
            return $this->cntr->get(LocalAccount::class);
        }

        // Get account list
        $accounts = $this->store->list(true);
        if (count($accounts) == 1) {
            return $this->store->get(array_keys($accounts)[0]);
        } elseif (count($accounts) == 0 && $account = $this->askNoAccount()) { 
            return $account;
        }

        // Create options
        $options = [];
        foreach ($accounts as $alias => $cdate) { 
            $acct = $this->store->get($alias);
            $name = $acct->getUsername() . '@' . $acct->getRepoAlias();
            $options[$alias] = $name . ' (Created ' . $cdate . ')';
        }

        // Get account
        $alias = $this->cli->getOption("To continue, select an account for this action:", $options, '', true);
        $acct = $this->store->get($alias);

        // Add to container
        $this->cntr->set(LocalAccount::class, $acct);

        // Return
        return $acct;
    }

    /**
     * If not accounts configured, ask to login or register.
     */
    private function askNoAccount():LocalAccount
    {

        // Send message
        $this->cli->send("No accounts found on this machine, and one will now be created.  If you have an existing account you wish to use, you need to import it first.  See 'apex help account import' for details.\r\n\r\n");

        // Get repo
        $repo = $this->network_helper->getRepo();

        // register new account
        $acct = $this->register($repo);
        return $acct;
    }

    /**
     * Display account
     */
    public function display(array $info):void
    {

        // Check e-amil verified
        if ($info['email_verified'] !== true) { 
            $info['email'] .= ' (Unverified)';
        }

        // Check phone verified
        if ($info['phone_verified'] !== true) { 
            $info['phone'] .= ' (Unverified)';
        }

        // Display profile
        $this->cli->sendHeader('Account Profile');
        $this->cli->send("    Username: $info[username]\r\n");
        $this->cli->send("Full Name:  $info[first_name] $info[last_name]\r\n");
        $this->cli->send("    E-Mail Address:  $info[email]\r\n");
        $this->cli->send("    Phone Number:  $info[phone]\r\n");
        $this->cli->send("Date Created:    $info[created_at]\r\n\r\n");

    }


    /**
     * Register
     */
    public function register(LocalRepo $repo):?LocalAccount
    {

        // Send header
        $this->debugger?->add(1, "Starting account registration wizard.", 'info');
        $this->cli->sendHeader('Account Registration');

        // Get profile
        list($username, $email, $password, $register_code, $sponsor) = 
            $this->getProfileInfo($repo);

        $this->debugger?->add(1, "Obtained account profile, generateing CSR.", 'info');

        // Generate CSR
        $common_name = $username . '@' . $repo->getAlias();
        if (!$csr = $this->certs->generate($common_name, $username)) { 
            $this->cli->error("Unable to generate CSR, please try again.");
            return null;
        }

        // Ask to use same key for SSH
        //$this->cli->send("For security, all traffic to the network is sent over SSH.  If desired, you may use the private key that was generated for signing also as your SSH key, or you may generate a new keypair for SSH access.\r\n\r\n");
        //if (true === ($this->cli->getConfirm("Use previously generated key for SSH? ", 'y'))) { 
            //$ssh = $csr->getRsaKey();
            //$this->cli->send("\r\n");
        //} else { 

            //// Generate new SSH key
            //$this->cli->send("\r\n");
            //$this->cli->sendHeader('SSH Key');
            //$ssh = $this->rsa_keys->generate($username . '-ssh');
        //}
        $ssh = $csr->getRsaKey();

        // Debug
        $this->debugger?->add(1, "Generated CSR, connecting to remote server to register account.", 'info');
        $this->cli->send("\r\nGenerating signing certificate... done\r\nRegistering account... ");

        // Register account
        $crt = $this->register->process($repo, $username, $password, $email, $register_code, $sponsor, $csr, $ssh);
        $csr->setCrt($crt);

        // Debug
        $this->debugger?->add(1, "Registration successful, verifying certificates.", 'info');
        $this->cli->send("done.\r\nVerifying certificates... ");

        // Verify
        $crt_name = $username . '.' . $repo->getAlias();
        if (!$this->verify_cert->verify($crt, $crt_name)) { 
            $this->cli->error("Uh oh, there was a problem verifying the signing certificate.  Please contact customer support.");
        }
        $this->cli->send("done.\r\nSaving account... ");

        // Save account
        $this->store->create($username, $email, $repo, $csr, $ssh);
        $this->cli->send("done.\r\n\r\n");

        // Save crt file(s)
        $pem_file = $csr->getRsaKey()->getAlias() . '.pem';
        file_put_contents(SITE_PATH . '/' . $pem_file, $csr->getRsaKey()->getPrivateKey());
        $saved_files = [$pem_file];

        // Save SSH key, if needed
        if ($csr->getRsaKey()->getAlias() != $ssh->getAlias()) { 
            file_put_contents(SITE_PATH . '/' . $ssh->getAlias() . '.pem', $ssh->getPrivateKey());
            $saved_files[] = $ssh->getAlias() . '.pem';
        }

        // Success message
        $this->cli->sendHeader('Registration Successful');
        $this->cli->send("Congratulations, your new account has been successfully registered with the username '$username' and e-mail address '$email'.  A new 4096 bit RSA private key(s) has been generated, and can be found along with your signing certificate within this directory at:\r\n\r\n");
        foreach ($saved_files as $file) { 
            $this->cli->send("    $file\r\n");
        }
        $this->cli->send("\r\n");
        $this->cli->send("WARNING: Please backup the above private key, and keep it safe.  It is required if you ever wish to publish commits or releases using this account on another machine.  The key can NOT be retrieved later, it was generated locally on this machine, and only you have a copy of it.  Please keep it safe.\r\n\r\n");
        $this->cli->send("NOTE: Once safely backed up, you may remove the .pem file from this directory as it is not needed at this location.\r\n\r\n");

        // Return
        return $this->store->get($username . '.' . $repo->getAlias());
    }

    /**
     * Get profile info
     */
    private function getProfileInfo(LocalRepo $repo):?array
    {

        // Send greeting
        $repo_url = 'https://' . $repo->getHttpHost() . '/<username>/';
        $this->cli->send("Please enter the username you wish to register with.  Aside from being the username you login with, your public repositories will be available at:\r\n\r\n");
        $this->cli->send("    $repo_url\r\n\r\n");

        // Get username
        do { 
            $username = strtolower($this->cli->getInput('Desired Username: '));
            if (strlen($username) < 3 || !preg_match("/^[a-zA-z0-9_-]+$/", $username)) { 
                $this->cli->send("Username must be minimum of 3 characters and can not contain spaces or special characters.  Please try again.\r\n\r\n");
                continue;
            }

            // Check if username exists
            if ($this->checkUsernameExists($repo, $username) === true) { 
                $this->cli->send("Username already exists, $username.  Please try again.\r\n\r\n");
                continue;
            }

            break;
        } while (true);

        // Get e-mail address
        do { 
            $email = strtolower($this->cli->getInput('E-Mail Address: '));
            if ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {  
                $this->cli->send("Invalid e-mail address.\r\n\r\n");
                continue;
            }
            break;
        } while (true);

        // Get password
        $password = $this->cli->getNewPassword();

        // Verify e-mail
        $register_code = $this->verifyEmail($repo, $email);

        // Get sponsor, if needed
        $sponsor = '';
        if ($repo->getAlias() == 'apex') {
            $this->cli->send("\r\n");
            $this->cli->send("If you were referred to Apex by someone, please enter their username below.  Otherwise, leave the below field blank and press enter to continue.\r\n\r\n");
            $sponsor = $this->cli->getInput('Sponsor: ');
        }

        // Return
        $this->cli->send("\r\n");
        return [$username, $email, $password, $register_code, $sponsor];
    }

    /**
     * Check username exists
     */
    private function checkUsernameExists(LocalRepo $repo, string $username):bool
    {
        $res = $this->network->post($repo, 'users/check_exists', ['username' => $username]);
        return $res['exists'];
    }

    /**
     * Verify e-mail address
     */
    private function verifyEmail(LocalRepo $repo, string $email):string
    {

        // Initialize
        $res = $this->network->post($repo, 'users/verify-email', ['email' => $email, 'action' => 'init']);
        $this->cli->send("An e-mail has been sent to $email with a six digit confirmation code, which you must enter below to proceed.\n\n");

        // Confirm code
        do {

            $code = $this->cli->getInput("Confirmation Code: ");
            $res = $this->network->post($repo, 'users/verify-email', ['code' => $code, 'email' => $email, 'action' => 'confirm']);

            // Check response
            $ok = isset($res['status']) && $res['status'] == 'ok' ? true : false;
            if ($ok === true) {
                return $res['register_code'];
            }

            // Get new code
            $this->cli->send("\nInvalid code.  Please try again or enter 'quit' to exit.\n\n");
    } while (true);

        // Return
        return $register_code;
    }

}


