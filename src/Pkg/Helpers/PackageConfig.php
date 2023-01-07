<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\Svc\{Container, Convert};
use Apex\App\Pkg\Config\{ConfigVars, Hashes, Menus, Services, BoxlistItems, DashboardItems};
use Apex\App\Network\Stores\PackagesStore;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\App\Exceptions\{ApexYamlException, ApexConfigException};
use Apex\App\Attr\Inject;


/**
     * Package config
 */
class PackageConfig
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Load configuration
     */
    public function load(string $pkg_alias, string $filename = 'package.yml'):array
    {

        // Check file exists
        $filepath = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/' . $filename;
        if ($filename == 'package.yml' && !file_exists($filepath)) { 
            throw new ApexConfigException("The package.yml file does not exist for the package, $pkg_alias");
        } elseif (!file_exists($filepath)) { 
            return [];
        }

        // Load yaml file
        try {
            $yaml = Yaml::parseFile($filepath);
        } catch (ParseException $e) { 
            throw new ApexYamlException("Unable to parse $filename YAML file, error: " . $e->getMessage());
        }

        // Return
        return $yaml;
    }

    /**
     * Install configuration
     */
    public function install(string $pkg_alias):void
    {

        // Load config
    $pkg_alias = $this->convert->case($pkg_alias, 'lower');
        $yaml = $this->load($pkg_alias);
        $registry = $this->load($pkg_alias, 'registry.yml');

        // Install config vars
        $config_vars = $this->cntr->make(ConfigVars::class, ['pkg_alias' => $pkg_alias]);
        $config_vars->install($yaml);

        // Install hashes
        $hashes = $this->cntr->make(Hashes::class, ['pkg_alias' => $pkg_alias]);
        $hashes->install($yaml);

        // Install menus
        $menus = $this->cntr->make(Menus::class, ['pkg_alias' => $pkg_alias]);
        $menus->install($yaml);

        // Install services
        $services = $this->cntr->make(Services::class, ['pkg_alias' => $pkg_alias]);
        $services->install($registry);

        // Install boxlist items
        $boxlist_items = $this->cntr->make(BoxlistItems::class, ['pkg_alias' => $pkg_alias]);
        $boxlist_items->install($yaml);
    }

    /**
     * Remove
     */
    public function remove(string $pkg_alias):void
    {

        // Load config
        $pkg_alias = $this->convert->case($pkg_alias, 'lower');
        $yaml = $this->load($pkg_alias);
        $registry = $this->load($pkg_alias, 'registry.yml');

        // Remove config vars
        $config_vars = $this->cntr->make(ConfigVars::class, ['pkg_alias' => $pkg_alias]);
        $config_vars->remove($yaml);

        // remove hashes
        $hashes = $this->cntr->make(Hashes::class, ['pkg_alias' => $pkg_alias]);
        $hashes->remove($yaml);

        // Remove menus
        $menus = $this->cntr->make(Menus::class, ['pkg_alias' => $pkg_alias]);
        $menus->remove($yaml);

        // Remove services
        $services = $this->cntr->make(Services::class, ['pkg_alias' => $pkg_alias]);
        $services->remove($registry);

        // Remove boxlist items
        $boxlist_items = $this->cntr->make(BoxlistItems::class, ['pkg_alias' => $pkg_alias]);
        $boxlist_items->remove($yaml);

        // Remove dashboard items
        $dashboard_items = $this->cntr->make(DashboardItems::class);
        $dashboard_items->remove($pkg_alias);
    }

}

