<?php
declare(strict_types = 1);

namespace Apex\App\Adapters;

use Apex\Svc\Convert;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\PackagesStore;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\Migrations\Exceptions\MigrationsYamlConfigException;

/**
 * Migrations config
 */
class MigrationsConfig
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    // Properties
    private string $table_name = 'internal_migrations';
    private array $author = [];
    private array $packages = [];

    /**
     * Get table name
     */
    public function getTableName():string
    {
        return $this->table_name;
    }

    /**
     * Set table name
     */
    public function setTableName(string $name):void
    {
        $this->table_name = $name;
    }

    /**
     * Get author
     */
    public function getAuthor():array
    {
        return $this->author;
    }

    /**
     * Set author username
     */
    public function setAuthorUsername(string $username):void
    {
        $this->author['username'] = $username;
    }

    /**
     * Set author name
     */
    public function setAuthorName(string $name):void
    {
        $this->author['full_name'] = $name;
    }

    /**
     * Set author e-mail
     */
    public function setAuthorEmail(string $email):void
    {
        $this->author['email'] = $email;
    }

    /**
     * Get packages
     */
    public function getPackages():array
    {

        // Go through all packages
        $packages = [];
        $list = $this->pkg_store->list();
        foreach ($list as $alias => $vars) {  
            $packages[$alias] = [
                'dir' => SITE_PATH . '/etc/' . $this->convert->case($alias, 'title') . '/Migrations', 
                'namespace' => "Etc\\" . $this->convert->case($alias, 'title') . "\\Migrations"
            ];
        }

        // return
        return $packages;
    }

    /**
     * Get single package
     */
    public function getPackage(string $alias = 'default'):?array
    {

        // Check if not exists
        if (!$pkg = $this->pkg_store->get($alias)) { 
            return null;
        }

        // Set variables
        $dir = SITE_PATH . '/etc/' . $pkg->getAliasTitle() . '/Migrations';
        $namespace = "Etc\\" . $pkg->getAliasTitle() . "\\Migrations";

        // Check for Doctrine entity paths
        $entity_paths = [];
        if (file_exists(SITE_PATH . '/boot/doctrine.yml')) { 
            $yaml = Yaml::parseFile(SITE_PATH . '/boot/doctrine.yml');
            $entity_paths = $yaml[$pkg->getAlias()] ?? [];
            $entity_paths = array_map( fn ($path) => SITE_PATH . '/' . trim($path, '/'), $entity_paths);
        }

        // Get info
        return [$dir, $namespace, $entity_paths];
    }

    /**
     * Set packages
     */
    public function addPackage(string $alias, string $dir, string $namespace):void
    {
        $this->packages[$alias] = [
            'dir' => $dir, 
            'namespace' => $namespace
        ];
    }

    /**
     * Delete package
     */
    public function deletePackage(string $alias):void
    {
        unset($this->packages[$alias]);
    }

    /**
     * Purge packages
     */
    public function purgePackages():void
    {
        $this->packages = [];
    }

}



