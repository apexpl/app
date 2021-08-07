<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\Convert;
use Apex\App\Network\Models\{LocalRepo, Certificate, LocalAccount, RsaKey};
use Apex\App\Network\Stores\{PackagesStore, RsaKeyStore};
use Apex\Opus\Opus;
use Apex\Db\Mapper\ToInstance;
use Apex\App\Exceptions\ApexAccountNotExistsException;
use Symfony\Component\Yaml\Yaml;

/**
 * Accounts store
 */
class AccountsStore extends AbstractStore
{

    /**
     * Constructor
     */
    public function __construct(
        private string $confdir = '',
        private Opus $opus, 
        private Convert $convert,
        private PackagesStore $pkg_store,
        private RsaKeyStore $rsa_store
    ) { 

        // Get confdir
        if ($this->confdir == '') { 
            $this->confdir = $this->determineConfDir();
        }
    }

    /**
     * List accounts
     */
    public function list(bool $with_cdate = false):array
    {

        // Check dir exists
        $accounts_dir = $this->confdir . '/accounts';
        if (!is_dir($accounts_dir)) { 
            return [];
        }

        // Go through directory
        $files = scandir($accounts_dir);
        foreach ($files as $file) { 

            // Skip, if needed
        if (!str_ends_with($file, '.yml')) { 
                continue;
            }
        $alias = preg_replace("/\.yml$/", '', $file);

            // Add to accounts
            if ($with_cdate === true) { 
                $info = stat("$accounts_dir/$file");
                $accounts[$alias] = $this->convert->date(date('Y-m-d H:i', $info['ctime'])   , true);
            } else { 
                $accounts[] = $alias;
            }
        }

        // Return
        return $accounts;
    }

    /**
     * Get
     */
    public function get(string $name):?LocalAccount
    {

        // Check account.yml file
        $yml_file = $this->confdir . '/accounts/' . $name . '.yml';
        if (!file_exists($yml_file)) { 
            return null;
        }

        // Load account
        $yaml = $this->loadYamlFile($yml_file);
        if (!isset($yaml['profile'])) { 
            throw new ApexAccountNotExistsException("Account YAML file does not have a profile array, $name");
        }

        // Map and return
        $account = ToInstance::map(LocalAccount::class, $yaml['profile']);
        return $account;
    }

    /**
     * Create account
     */
    public function create(string $username, string $email, LocalRepo $repo, Certificate $csr, RsaKey $ssh):void
    {

        // Set variables
        $vars = [
            'username' => $username, 
            'email' => $email, 
            'repo' => $repo->getAlias(),
            'ssh_key_alias' => $ssh->getAlias(), 
            'sign_key_alias' => $csr->getRsaKey()->getAlias(), 
            'csr' => $csr->getCsr(), 
            'crt' => $csr->getCrt()
        ];

        // Create account
        $this->opus->build('account', $this->confdir, $vars);
    }

    /**
     * Save
     */
    public function save(LocalAccount $acct):void
    {

        // Get profile
        $yaml = [
            'profile' => $acct->toArray()
        ];

        // Save yaml file
        $yaml_file = $this->confdir . '/accounts/' . $acct->getUsername() . '.' . $acct->getRepoAlias() . '.yml';
        if (!is_dir("$this->confdir/accounts")) { 
            mkdir("$this->confdir/accounts", 0755, true);
        }
        file_put_contents($yaml_file, Yaml::dump($yaml));
    }

    /**
     * Delete
     */
    public function delete(LocalAccount $acct):void
    {

        // Go through packages, remove local_user
        $packages = $this->pkg_store->list();
        foreach ($packages as $pkg_alias => $vars) { 
            if ($vars['local_user'] != $acct->getUsername()) { 
                continue;
            }
            $pkg = $this->pkg_store->get($pkg_alias);
            $pkg->setLocalUser('');
        }

        // GO through all accounts, gather list of keys used
        $keys = [];
        foreach ($this->list() as $alias) { 
            if ($alias == $acct->getUsername() . '.' . $acct->getRepoAlias()) { 
                continue;
            }
            $tmp_acct = $this->get($alias);
            $keys[] = $tmp_acct->getSignKey();
            $keys[] = $tmp_acct->getSshKey();
        }
        $keys = array_unique($keys);

        // Delete sign key, if needed
        if (!in_array($acct->getSignKey(), $keys)) { 
            $this->rsa_store->delete($acct->getSignKey());
        }

        // Delete ssh key, if needed
        if ($acct->getSignKey() != $acct->getSshKey() && !in_array($acct->getSshKey(), $keys)) { 
            $this->rsa_store->delete($acct->getSshKey());
        }

        // Delete yaml file
        $yaml_file = $this->confdir . '/accounts/' . $acct->getUsername() . '.' . $acct->getRepoAlias() . '.yml';
        if (file_exists($yaml_file)) { 
            unlink($yaml_file);
        }

    }


}



