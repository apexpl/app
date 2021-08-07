<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, CertificateHelper};
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Stores\{ReposStore, CertificateStore};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Exceptions\ApexCertificateNotExistsException;

/**
 * Grand manager
 */
class RequestManager implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(CertificateStore::class)]
    private CertificateStore $cert_store;

    #[Inject(CertificateHelper::class)]
    private CertificateHelper $cert_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $username = $args[0] ?? '';
        $repo_alias = $opt['repo'] ?? 'apex';

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with alias, $repo_alias");
            return;
        }

        // Get account
        $account = $this->acct_helper->get();
        $crt_name = $account->getUsername() . '.' . $username . '.' . $repo_alias;

        // Start request
        $request = [
            'username' => $username,
            'type' => 'manager'
        ];

        // Try to get certificate
        try {
            $crt = $this->cert_store->get($crt_name);
        } catch (ApexCertificateNotExistsException $e) { 
            $common_name = $account->getUsername() . '.' . $username . '@' . $repo_alias;
            $csr = $this->cert_helper->generate($common_name);
            $request['public_key'] = $csr->getRsaKey()->getPublicKey();
            $request['csr'] = $csr->getCsr();
        }

        // Get optional message
        $cli->send("\r\n\r\n");
        $cli->send("If desired, you may include an optional message to the account holder who will process the access request.\r\n\r\n");
        $request['message'] = $cli->getInput('Optional Message: ');

        // Send request
        $this->network->setAuth($account);
        $res = $this->network->post($repo, 'acl/request', $request);

        // Success
        $cli->send("Successfully sent manager access request to '$username'.  You will be notified once the request has been granted or rejected.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Request Manager Access',
            usage: 'acl request-manager <USERNAME>',
            description: "Request to be given manager access to a user's account, allowing you to create and manage packages / projects on their account."
        );

        $help->addParam('username', "The username of the user to request access from.");
        $help->addExample('./apex acl request-manager jsmith');

        // Return
        return $help;
    }

}


