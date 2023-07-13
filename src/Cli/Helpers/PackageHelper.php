<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\Convert;
use Apex\App\Cli\CLi;
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\{PackagesStore, AccountsStore};
use Apex\App\Network\Models\{LocalRepo, LocalPackage, LocalAccount};
use Apex\App\Network\NetworkClient;
use Apex\Db\Mapper\ToInstance;
use Apex\App\Attr\Inject;

/**
 * Package helper
 */
class PackageHelper
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(AccountsStore::class)]
    private AccountsStore $acct_store;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    // Properties
    private ?LocalAccount $account = null;

    /**
     * Get package
     */
    public function get(string $pkg_alias):?LocalPackage
    {

        // Ensure package specified
        if ($pkg_alias == '') { 
            $this->cli->error("You did not specify a package alias.");
            return null;
        }

        // Check for serial format
        $username = null;
        if (preg_match("/^([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)$/", $pkg_alias, $match)) { 
            $username = $match[1];
            $pkg_alias = $match[2];
        }
        $pkg_alias = $this->convert->case($pkg_alias, 'lower');

        // Get package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $this->cli->error("Package does not exist with alias, $pkg_alias");
            return null;
        }

        // Return
        return $pkg;
    }

    /**
     * Get package serial
     */
    public function getSerial(string $pkg_alias):?string
    {

        // Ensure alias defined
        if ($pkg_alias == '') { 
            $this->cli->error("You did not define a package alias.");
            return null;
        }

        // Check for serial format
        $username = null;
        if (preg_match("/^([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/", $pkg_alias, $match)) { 
            $username = strtolower($match[1]);
            $pkg_alias = $match[2];
        }
        $pkg_alias = $this->convert->case($pkg_alias, 'lower');

        // Get serial as needed
        if ($username !== null) { 
            $pkg_serial = $username . '/' . $pkg_alias;
        } elseif (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $pkg_serial = 'apex/' . $pkg_alias;
        } elseif (null === ($author = $pkg->getAuthor())) { 
            $account = $this->acct_helper->get();
            $pkg->setAuthor($account->getUsername());
            $pkg_serial = $account->getUsername() . '/' . $pkg_alias;
        } else { 
            $pkg_serial = $author .'/' . $pkg_alias;
        }

        // Return
        return $pkg_serial;
    }

    /**
     * Check package access
     */
    public function checkPackageAccess(LocalRepo $repo, string $pkg_alias, string $column = 'can_read', bool $is_install = false, ?string $license_id = null):?LocalPackage
    {

        // Check repository for package
        $is_install = $is_install === true ? 1 : 0;
        $pkg_serial = $this->getSerial($pkg_alias);
        if (!$res = $this->network->post($repo, 'repos/check', ['pkg_serial' => $pkg_serial, 'is_install' => $is_install])) { 
            $this->cli->error("Package '$pkg_alias' does not exist on the repository '$repo_alias'");
            return null;
        } elseif ($res['exists'] !== true) { 
            $this->cli->error("Package '$pkg_alias' does not exist on the repository '" . $repo->getAlias() . "'");
            return null;
        }

        // Request authentication, if can not download
        if ($res[$column] !== true) {

            // Check license if, if possible
            if ($column == 'can_read' && $this->account === null && count($this->acct_store->list()) == 0) {
                if (null === ($res = $this->checkLicenseId($pkg_serial, $repo, $license_id))) {
                    return null;
                }
            }

            // Check authorization, if appropriate
            if ($res[$column] !== true) {
                if ($this->account === null) { 
                    $this->account = $this->acct_helper->get();
                }
                $this->network->setAuth($this->account);
                $res = $this->network->post($repo, 'repos/check', ['pkg_serial' => $pkg_serial, 'is_install' => $is_install]);
            }

            // Ask for license id, if needed
            if ($column == 'can_read' && $res[$column] === false && $license_id === null) {
                if (null === ($res = $this->checkLicenseId($pkg_serial, $repo, $license_id))) {
                    return null;
                }
            }
        }

        // Check access
        if ($res[$column] !== true) { 
            return null;
        }
        $license_id = $res['license_id'] ?? null;

        // Get package object
        $res['repo_alias'] = $repo->getAlias();
        $res['local_user'] = $this->account === null ? '' : $this->account->getUsername();
        $res['license_id'] = $license_id;
        $pkg = ToInstance::map(LocalPackage::class, $res);

        // Return
        return $pkg;
    }

    /**
     * Chec license id
     */
    private function checkLicenseId(string $pkg_serial, LocalRepo $repo, ?string $license_id = null):?array
    {

        // Get license id, if needed
        if ($license_id === null) {
            $this->cli->sendHeader("License ID");
            $this->cli->send("If you have purchased the $pkg_serial package and have a license ID for it, please enter it below.  Otherwise, simply leave the field below blank to continue.\n");
            $license_id = $this->cli->getInput('License ID: ');
            if (trim($license_id) == '') {
                return null;
        }
        }

        // Send http request
        $res = $this->network->post($repo, 'repos/check', [
            'pkg_serial' => $pkg_serial, 
            'license_id' => $license_id,
            'is_install' => 1
        ]);

        // Check response
        if ($res['can_read'] !== true) {
            return null;
        }

        // Return
        return $res;
    }

    /**
     * Check for duplicate
     */
    public function checkDuplicate(string $pkg_alias, LocalRepo $repo):bool
    {

        // Check if package exists in repo
        $res = $this->network->post($repo, 'repos/search', ['pkg_alias' => $pkg_alias, 'exact_match' => 1]);
        if ($res['count'] > 0) { 
            $this->cli->send("The following similar packages were found on the network:\r\n\r\n");

            foreach ($res['packages'] as $pkg) { 
                $this->cli->send("    " . $pkg['serial'] . ' (' . 'https://' . $repo->getHttpHost() . '/' . $pkg['author'] . '/' . $pkg['alias'] . "/\r\n");
            }
            $this->cli->send("\r\nThere may be naming conflicts if a user tries to install both your package and one of the above packages on the same system.\r\n\r\n");

            // Confirm package creation
            if (!$this->cli->getConfirm("Continue to create the package?")) { 
                $this->cli->send("Ok, goodbye.\r\n\r\n");
                return false;
            }
        }

        // Return
        return true;

    }

}


