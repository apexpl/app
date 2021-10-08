<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\Svc\Convert;
use Apex\App\Sys\Utils\Io;
use Apex\App\Exceptions\ApexYamlException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Component registry
 */
class Registry
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Io::class)]
    private Io $io;

    /**
     * Constructor
     */
    public function __construct(
        private string $pkg_alias = ''
    ) { 

    }

    /**
     * Add entry
     */
    public function add(string $type, string $key, ?string $value = null):void
    {

        // Load
        $yaml = $this->load();
        if (!isset($yaml[$type])) { 
            $yaml[$type] = [];
        }

        // Add entry
        if ($value !== null && !isset($yaml[$type][$key])) { 
            $yaml[$type][$key] = $value;
        } elseif ($value === null && !in_array($key, $yaml[$type])) { 
            $yaml[$type][] = $key;
        } else { 
            return;
        }

        // Save yaml file
        $this->save($yaml);
    }

    /**
     * Remove
     */
    public function remove(string $type, string $key):bool
    {

        // Load yaml file
        $yaml = $this->load();
        if (!isset($yaml[$type])) { 
            return false;
        }

        // Remove from registry
        $found = false;
        if (isset($yaml[$type][$key])) { 
            unset($yaml[$type][$key]);
            $found = true;
        } elseif (($index = array_search($key, $yaml[$type])) !== false) { 
            array_splice($yaml[$type], $index, 1);
            $found = true;
        }

        // Save file
        $this->save($yaml);
        return $found;
    }

    /**
     * Load components
     */
    public function load():array
    {

        // Get yaml file 
        $yaml_file = SITE_PATH . '/etc/' . $this->convert->case($this->pkg_alias, 'title') . '/registry.yml';

        // Check file exists
        if (!file_exists($yaml_file)) { 
            return [];
        }

        // Load yaml file
        try {
            $yaml = Yaml::parseFile($yaml_file);
        } catch (ParseException $e) { 
            throw new ApexYamlException("Unable to parse YAML file at $yaml_file, Error: " . $e->getMessage());
        }

        // Return
        return $yaml;
    }

    /**
     * Save yaml file
     */
    public function save(array $yaml):void
    {

        // Get yaml file
        $yaml_file = SITE_PATH . '/etc/' . $this->convert->case($this->pkg_alias, 'title') . '/registry.yml';

        // Save file
        $header = "\n##########\n# Components Registry\n#\n# This file is auto-generated, and please do not modify unless you know what you are doing.\n##########\n\n";
        file_put_contents($yaml_file, $header . Yaml::dump($yaml));
    }

}


