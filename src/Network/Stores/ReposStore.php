<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\Container;
use Apex\App\Network\Models\LocalRepo;
use Symfony\Component\Yaml\Yaml;

/**
 * Repos store
 */
class ReposStore extends AbstractStore
{

    #[Inject(Container::class)]
    private Container $cntr;

    // Properties
    private array $repos = [];

    /**
     * List
     */
    public function list():array
    {

        // Check if loaded
        if (count($this->repos) > 0) { 
            return $this->repos;
        }

        // Load yaml config
        $yaml = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        $this->repos = $yaml['repos'] ?? [];

        // Return
        return $this->repos;
    }

    /**
     * Get repo
    */
    public function get(string $repo_alias):?LocalRepo
    {

        $repos = $this->list();
        if (!isset($repos[$repo_alias])) { 
            return null;
        }

        // Get repo vars
        $vars = $repos[$repo_alias];
        $vars['alias'] = $repo_alias;

        // Make and return
        $repo = $this->cntr->make(LocalRepo::class, $vars);
        return $repo;
    }

    /**
     * Save
     */
    public function save(LocalRepo $repo):void
    {

        $config = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        if (!isset($config['repos'])) { 
            $config['repos'] = [];
        }

        // Save new config
        $config['repos'][$repo->getAlias()] = $repo->toArray();
        file_put_contents(SITE_PATH . '/etc/.config.yml', Yaml::dump($config));
    }

    /**
     * Delete
     */
    public function delete(string $repo_alias):void
    {

        // Load config
        $config = $this->loadYamlFile(SITE_PATH . '/etc/.config.yml');
        if (!isset($config['repos'])) { 
            return;
        }

        // Delete and save
        unset($config['repos'][$repo_alias]);
        file_put_contents(SITE_PATH . '/etc/.config.yml', Yaml::dump($config));
    }


}

