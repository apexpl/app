<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{AccountHelper, PackageHelper, AclHelper};
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Request access to branch
 */
class RequestBranch implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(AclHelper::class)]
    private AclHelper $acl_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $pkg_serial = $this->pkg_helper->getSerial(($args[0] ?? ''));
        $branch_name = $args[1] ?? '';
        $repo_alias = $opt['repo'] ?? 'apex';

        // Perform checks
        if ($pkg_serial == '' || !preg_match("/^([a-zA-Z0-9_-]+)\/[a-zA-Z0-9_\-]+$/", $pkg_serial, $match)) { 
            $cli->error("Invalid package specified, $pkg_serial.  Must be in format of author/alias.");
            return;
        } elseif ($branch_name == '' || !preg_match("/^[a-zA-Z0-9_\-]+$/", $branch_name)) { 
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

        // Ensure package exists
        $res = $this->network->post($repo, 'repos/check', ['pkg_serial' => $pkg_serial]);
        if ($res['exists'] != 1) { 
            $cli->error("The package '$pkg_serial' does not exist on the repository.");
            return;
        }

        // Start request
        $request = [
            'username' => $username,
            'type' => 'branch',
            'pkg_serial' => $pkg_serial,
            'branch_name' => $branch_name
        ];

        // Get csr
        $common_name = $account->getUsername() . '.' . $username . '@' . $repo_alias;
        if (null !== ($csr = $this->acl_helper->getRequestCsr($account, $common_name))) {
            $request['public_key'] = $csr->getRsaKey()->getPublicKey();
            $request['csr'] = $csr->getCsr();
        }

        // Get account certificate
        $cert = $account->getCertificate();

        // Send header
        $cli->sendHeader("Request Access to Branch $pkg_serial / $branch_name");
        $cli->send("You are requesting access to the branch '$pkg_serial / $branch_name' with the following certificate:\r\n\r\n");
        foreach ($cert->getIssuedTo() as $line) {
            $cli->send("    $line\r\n");
        }
        $cli->send("\r\n");
        $cli->send("    Fingerprint:  " . $cert->getFingerprint() . "\r\n\r\n");

        // Get optional message
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


