<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\Container;
use Apex\App\Network\Models\LocalPackage;
use Symfony\Component\Yaml\Yaml;
use Apex\App\Attr\Inject;
use DateTime;

/**
 * Packages store
 */
class PackagesStore extends AbstractStore
{

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * List
     */
    public function list():array
    {

        // Load yaml config
        $yaml = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        $packages = $yaml['packages'] ?? [];

        // Return
        return $packages;
    }

    /**
     * Get package
    */
    public function get(string $pkg_alias):?LocalPackage
    {

        // Check for serial format
        if (preg_match("/^([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)$/", $pkg_alias, $m)) { 
            $pkg_alias = $m[2];
        }

        // Get package list
        $packages = $this->list();
        if (!isset($packages[$pkg_alias])) { 
            return null;
        }

        // Get package vars
        $vars = $packages[$pkg_alias];
        $vars['alias'] = $pkg_alias;
        $vars['installed_at'] = new DateTime(date('Y-m-d H:i:s', $vars['installed_at']));

        // Check for nulls
        foreach (['author','local_user','repo_alias'] as $var) { 
            if ($vars[$var] === null) { 
                $vars[$var] = '';
            }
        }

        // Make and return
        $pkg = $this->cntr->make(LocalPackage::class, $vars);
        return $pkg;
    }

    /**
     * Save
     */
    public function save(LocalPackage $pkg):void
    {

        $config = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        if (!isset($config['packages'])) { 
            $config['packages'] = [];
        }

        // Save new config
        $config['packages'][$pkg->getAlias()] = $pkg->toArray();
        file_put_contents(SITE_PATH . '/etc/.config.yml', Yaml::dump($config, 6));
    }

    /**
     * Delete package
     */
    public function delete(string $pkg_alias):void
    {

        // Load config
        $config = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        if (!isset($config['packages'])) { 
            return;
        }

        // Delete and save
        unset($config['packages'][$pkg_alias]);
        file_put_contents(SITE_PATH . '/etc/.config.yml', Yaml::dump($config, 5));
    }



}

