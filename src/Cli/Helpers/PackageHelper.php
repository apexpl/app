<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

use Apex\Svc\Convert;
use Apex\App\Cli\CLi;
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\{LocalRepo, LocalPackage, LocalAccount};
use Apex\App\Network\NetworkClient;
use Apex\Db\Mapper\ToInstance;

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
    public function checkPackageAccess(LocalRepo $repo, string $pkg_alias, string $column = 'can_read', bool $is_install = false):?LocalPackage
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
            if ($this->account === null) { 
                $this->account = $this->acct_helper->get();
            }
            $this->network->setAuth($this->account);
            $res = $this->network->post($repo, 'repos/check', ['pkg_serial' => $pkg_serial, 'is_install' => $is_install]);
        }

        // Check access
        if ($res[$column] !== true) { 
            return null;
        }

        // Get package object
        $res['repo_alias'] = $repo->getAlias();
        $res['local_user'] = $this->account === null ? '' : $this->account->getUsername();
        $pkg = ToInstance::map(LocalPackage::class, $res);

        // Return
        return $pkg;
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


