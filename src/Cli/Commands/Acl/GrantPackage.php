<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Acl;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\{ReposStore, CertificateStore};
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Exceptions\ApexCertificateNotExistsException;
use Apex\App\Attr\Inject;

/**
 * Grant role
 */
class GrantPackage implements CliCommandInterface
{

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(CertificateStore::class)]
    private CertificateStore $cert_store;

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
        $username = $args[1] ?? '';
        $role = $args[2] ?? '';
        $repo_alias = $opt['repo'] ?? 'apex';

        // Get account
        $account = $this->acct_helper->get();

        // Check pkg_serial
        if (!str_contains($pkg_serial, '/')) { 
            $pkg_serial = $account->getUsername() . '/' . $pkg_serial;
        }

        // Perform checks
        if ($pkg_serial == '' || !preg_match("/^[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+$/", $pkg_serial)) { 
            $cli->error("Invalid package specified, $pkg_serial.  Must be in format of author/alias.");
            return;
        } elseif ($username == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $username)) { 
            $cli->error("Invalid username specified, $username");
            return;
        } elseif (!in_array($role, ['admin', 'maintainer', 'team', 'readonly'])) { 
            $cli->error("Invalid role specified, $role.  Supported values are: admin, maintainer, team, readonly");
            return;
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with alias, $repo_alias");
            return;
        }

        // Check for certificate
        try {
            $crt_name = $username . '.' . $account->getUsername() . '.' . $repo_alias;
            $crt = $this->cert_store->get($crt_name);
        } catch (ApexCertificateNotExistsException $e) { 
            $cli->error("The user '$username' does not have a certificate signed by you.  Please ask the user to request access to the package instead, see 'apex help acl request-package' for details.");
            return;
        }

        // Send API call
        $this->network->setAuth($account);
        $res = $this->network->post($repo, 'acl/grant_package', [
            'pkg_serial' => $pkg_serial,
            'username' => $username,
            'role' => $role
        ]);

        // Success
        $cli->send("Successfully granted role of '$role' to the user / group '$username' of the package '$pkg_serial'.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Grant User Access to Package',
            usage: 'acl grant-package <PKG_SERIAL> <USERNAME> <ROLE> [--repo=<REPO>]',
            description: "Grants an access role to a user to the specified package."
        );

        // Params
        $help->addParam('pkg_serial', 'The package serial (author/alias) of the package to add access to.');
        $help->addParam('username', 'The username of the person to provide access to.');
        $help->addParam('role', "The role which to grant access for.  Supported values are: admin, maintainer, team, readonly");
        $help->addFlag('repo', 'The repository alias to grant access to.  Defaults to main apex repository.');
        $help->addExample('./apex acl grant-role myuser/shop jsmith maintainer');

        // Return
        return $help;
    }

}


