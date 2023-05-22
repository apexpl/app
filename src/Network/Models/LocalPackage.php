<?php
declare(strict_types = 1);

namespace Apex\APp\Network\Models;

use Apex\Svc\{Container, Convert};
use Apex\App\Network\Models\{LocalRepo, LocalAccount};
use Apex\App\Network\Stores\{AccountsStore, ReposStore};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Pkg\Helpers\PackageConfig;
use Apex\App\Exceptions\ApexRepoNotExistsException;
use Apex\App\Attr\Inject;
use DateTime;
use redis;

/**
 * Local package
 */
class LocalPackage
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(AccountsStore::class)]
    private AccountsStore $store;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(redis::class)]
    private redis $redis;

    // Properties
    private bool $is_modified = false;

    /**
     * Constructor
     */
    public function __construct(
        private bool $is_local = false,
        private string $type = 'package',  
        private string $version = '1.0.0', 
        private ?DateTime $installed_at = null, 
        private string $local_user = '',  
        private string $author = '',  
        private string $repo_alias = '',  
        private string $alias = ''  
    ) { 

    }

    /**
     * Get is version controlled
     */
    public function isVersioncontrolled():bool
    {
        return $this->is_local === true || is_dir(SITE_PATH . '/.apex/svn/' . $this->alias) ? true : false;
    }

    /**
     * Is modified?
     */
    public function isModified():bool
    {
        return $this->is_modified;
    }

    /**
     * Is new
 */
    public function isLocal():bool
    {
        return $this->is_local;
    }

    /**
     * Get type
     */
    public function getType():string
    {
        return $this->type;
    }

    /**
     * Get version
     */
    public function getVersion():string
    {
        return $this->version;
    }

    /**
     * Get installed at
     */
    public function getInstalledAt():?DateTime
    {
        return $this->installed_at;
    }

    /**
     * Get local user
     */
    public function getLocalUser():?string
    {

        // Lookup account, if needed
        if ($this->local_user == '') { 
            $list = $this->store->list();
            if (in_array($this->author . '.' . $this->repo_alias, $list)) { 
                $this->local_user = $this->author;
                $this->is_modified = true;
            }
        }
        $this->redis->hdel('config:project', 'local_user');

        // Check for project
        if ($this->type == 'project') {
            if (!$username = $this->redis->hget('config:project', 'local_user')) {
                $username = $this->acct_helper->get()->getUsername();
                $this->redis->hset('config:project', 'local_user', $username);
            }
            $this->local_user = $username;
        }

        // Get account, if we don't have one
        if ($this->local_user == '') { 
            $acct = $this->acct_helper->get();
            $this->local_user = $acct->getUsername();
            if ($this->author == '') { 
                $this->author = $this->local_user;
            }
            $this->is_modified = true;
        }

        // Return
        return $this->local_user;
    }

    /**
     * Get local account
     */
    public function getLocalAccount():?LocalAccount
    {

        // Check for user
        $local_user = $this->getLocalUser();
        if ($local_user == '') { 
            return null;
        }

        // Get account and return
        return $this->store->get($local_user . '.' . $this->repo_alias);
    }

    /**
     * Get author
     */
    public function getAuthor():string
    {

        if ($this->author == '' && $this->getLocalUser() != '') { 
            $this->author = $this->getLocalUser();
        }

        // Return
        return $this->author;
    }

    /**
     * Get repo alias
     */
    public function getRepoAlias():string
    {
        return $this->repo_alias;
    }

    /**
     * Get alias
     */
    public function getAlias():string
    {
        return $this->alias;
    }

    /**
     * Get alias - title case
     */
    public function getAliasTitle():string
    {
        return $this->convert->case($this->alias, 'title');
    }

    /**
     * Get serial
     */
    public function getSerial():string
    {
        return $this->getAuthor() . '/' . $this->getAlias();
    }

    /**
     * Get signing crt name
     */
    public function getCrtName():string
    {

        // Get crt name
        $crt_name = $this->getLocalUser();
        if ($this->getLocalUser() != $this->getAuthor()) { 
            $crt_name .= '.' . $this->getAuthor();
        }

        // Return
        $crt_name .= '.' . $this->getRepoAlias();
        return $crt_name;
    }

    /**
     * Get repo
     */
    public function getRepo():?LocalRepo
    {
        return $this->repo_store->get($this->repo_alias);
    }

    /**
     * Get SVN repo
     */
    public function getSvnRepo():?SvnRepo
    {

        // Check
        if ($this->repo_alias == '') { 
            return null;
        } 
        return $this->cntr->make(SvnRepo::class, ['pkg' => $this]);
    }

    /**
     * Get config
     */
    public function getConfig():array
    {
        $config = $this->cntr->make(PackageConfig::class, ['pkg_alias' => $this->alias]);
        return $config->load($this->alias);
    }

    /**
     * Get registry
     */
    public function getRegistry():array
    {
        $config = $this->cntr->make(PackageConfig::class, ['pkg_alias' => $this->alias]);
        return $config->load($this->alias, 'registry.yml');
    }

    /**
     * Set is local
     */
    public function setIsLocal(bool $is_local):void
    {
        $this->is_local = $is_local;
    }

    /**
     * Set local user
     */
    public function setLocalUser(string $username):void
    {
        $this->local_user = $username;
        $this->is_modified = true;
    }

    /**
     * Set author
     */
    public function setAuthor(string $author):void
    {
        $this->author = $author;
        $this->is_modified = true;
    }

    /**
     * Set version
     */
    public function setVersion(string $version):void
    {
        $this->version = $version;
        $this->is_modified = true;
    }

    /**
     * Set repo alias
     */
    public function setRepoAlias(string $alias):void
    {
        $this->repo_alias = $alias;
        $this->is_modified = true;
    }

    /**
     * To Array
     */
    public function toArray():array
    {

        // Check installed_at
        if ($this->installed_at === null) { 
            $this->installed_at = new \DateTime();
        }

        // Set vars
        $vars = [
            'is_local' => $this->is_local,
            'type' => $this->type, 
            'version' => $this->version, 
            'author' => $this->author, 
            'local_user' => $this->local_user, 
            'repo_alias' => $this->repo_alias, 
            'installed_at' => $this->installed_at?->getTimestamp() 
        ];

        // Return
        return $vars;
    }

    /**
     * Get json request
     */
    public function getJsonRequest():array
    {

        // Load package config
        $yaml = $this->getConfig();
        $general = $yaml['general'] ?? [];

        // Set request
        $request = [
            'type' => $this->type,
            'alias' => $this->getAlias(),
            'pkg_serial' => $this->getSerial(),
            'author' => $this->getAuthor(),
            'category' => $general['category'] ?? '', 
            'license' => $general['license'] ?? 'MIT', 
            'access' => $general['access'] ?? 'public', 
            'price' => $general['price'] ?? 0, 
            'price_recurring' => $general['price_recurring'] ?? 0, 
            'price_interval' => $general['price_interval'] ?? '', 
            'description' => $general['description'] ?? '',
            'web_hooks' => $yaml['web_hooks'] ?? []
        ];

        // Check for ACLs
        $acl = $yaml['acl'] ?? [];
        foreach (['trunk','branches','releases','issues','rfc'] as $type) { 
            if (!isset($acl[$type])) { 
                continue;
            }
            $request['acl_' . $type] = $acl[$type];
        }

        // Return
        return $request;
    }


}


