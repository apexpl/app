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
 * Request access to branch
 */
class RequestBranch implements CliCommandInterface
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
        $pkg_serial = $args[0] ?? '';
        $branch_name = $args[1] ?? '';
        $repo_alias = $opt['repo'] ?? 'apex';

        // Perform checks
        if ($pkg_serial == '' || !preg_match("/^([a-zA-Z0-9_-]+)\/[a-zA-Z0-9_-]+$/", $pkg_serial, $match)) { 
            $cli->error("Invalid package specified, $pkg_serial.  Must be in format of author/alias.");
            return;
        } elseif ($branch_name == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $branch_name)) { 
            $cli->error("Invalid branch name specified.");
            return;
        }
        $username = $match[1];

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
            'type' => 'branch',
            'pkg_serial' => $pkg_serial,
            'branch_name' => $branch_name
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
        $cli->send("Successfully sent branch access request to '$username'.  You will be notified once the request has been granted or rejected.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Request Branch Access',
            usage: 'acl request-branch <PKG_SERIAL> <BRANCH_NAME> [--repo=]',
            description: " Request access to a branch on a package owned by another account."
        );

        $help->addParam('pkg_serial', "The package formatted in username/alias to request access of.");
        $help->addParam('branch_name', 'The name of the branch to request access to.');
        $help->addFlag('--repo', 'Optional repository alias of the request.  Defaults to main Apex repository.');
        $help->addExample('./apex acl request-branch jsmith/myshop new-feature');

        // Return
        return $help;
    }

}


