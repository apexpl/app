<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Sign\AbstractSigner;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Pending certs
 */
class Pending extends AbstractSigner implements CliCommandInterface
{

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    // Set options
    private array $options = [
        'grant' => 'Grant Access',
        'reject' => 'Reject Request',
        'skip' => 'Skip, do nothing'
    ];

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $repo_alias = $opt['repo'] ?? 'apex';

        // Get repo
        if (!$this->repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with the alias, $repo_alias");
            return;
        }

        // Get account
        $this->account = $this->acct_helper->get();

        // Send request
        $this->network->setAuth($this->account);
        $res = $this->network->post($this->repo, 'acl/pending', []);

        // Check for no pending
        if (count($res['signer']) == 0 && count($res['signee']) == 0) { 
            $cli->send("\r\nThere are no pending access requests on your account.\r\n\r\n");
            return;
        }

        // Process awaiting approval
        foreach ($res['signer'] as $row) { 
            $this->processPendingRequest($cli, $row);
        }

    }

    /**
     * Process pending request
     */
    private function processPendingRequest(Cli $cli, array $row):void
    {

        // Get access type
        $access_type = match($row['type']) { 
            'manager' => 'Account Manager',
            'package' => 'Package ' . $row['pkg_serial'] . ' (' . $row['role'] . ')',
            'branch' => 'Branch ' . $row['branch_name'] . ' on package ' . $row['pkg_serial'],
            default => 'Certificate Only'
        };

        // Display request
        $cli->sendHeader($row['username'] . ' -- ' . $access_type);
        $cli->send("The below request is currently pending approval:\r\n\r\n");
        $cli->send("Username:         $row[username]\r\n");
        $cli->send("Full Name:       $row[full_name]\r\n");
        $cli->send("E-Mail Address:  $row[email]\r\n");
        $cli->send("Access Requested: $access_type\r\n");
        $cli->send("Message:          $row[message]\r\n\r\n");

        // Get option
        $action = $cli->getOption('How would you like to handle the above request?', $this->options, '', true);
        if ($action == 'skip') { 
            $cli->send("Ok, skipping.\r\n\r\n");
            return;
        } elseif ($action == 'grant') { 
            $this->grantRequest($cli, $row);
        } else { 
            $this->rejectRequest($cli, $row);
        }

    }

    /**
     * Grant request
     */
    private function grantRequest(Cli $cli, array $row):void
    {

        // Sign certificate, if necessary
        $crt_out = '';
        if ($row['csr'] != '') { 

            // Get certificate and private key
            if (!$cert = $this->account->getCertificate()) { 
                $cli->error("Unable to obtain signing certificate for local account.");
                return;
            }
            $privkey = $this->unlockPrivateKey($cert->getRsaKey());

            // Sign CSR
            $signer_crt = openssl_x509_read($cert->getCrt());
            if (!$crt = openssl_csr_sign($row['csr'], $signer_crt, $privkey, 0, ['digest_alg' => 'sha384'])) { 
                $cli->error("Unable to sign certificate request for unknown reason.  Please ensure the request, private key, and signing certificate are all correct.");
                return;
            }
            openssl_x509_export($crt, $crt_out);
        }

        // Send API request
        $res = $this->network->post($this->repo, 'acl/grant_request', [
            'request_id' => $row['id'],
            'crt' => $crt_out
        ]);

        // Success
        $cli->send("Successfully granted access request to $row[username]\r\n\r\n");
    }

    /**
     * Reject request
     */
    private function rejectRequest(Cli $cli, array $row):void
    {

        // Send API request
        $res = $this->network->post($this->repo, 'acl/reject_request', [
            'request_id' => $row['id']
        ]);

        // Success
        $cli->send("Successfully rejected access request to $row[username]\r\n\r\n");
    }


    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'View Pending Certificates',
            usage: 'certs pending',
            description: "View all pending certificate signing requests on your account."
        );
        $help->addExample('./apex certs pending');

        // Return
        return $help;
    }

}


