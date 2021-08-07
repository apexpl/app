<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\Svc\Convert;
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

        // Check version control, add to global registry
        $this->checkVersionControl($type, $key, $value);
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

    /**
     * Check version control
     */
    private function checkVersionControl(string $type, string $key, ?string $value = null):void
    {

        // Check svn dir
        $svn_dir = SITE_PATH . '/.apex/svn/' . $this->pkg_alias;
        if (!is_dir($svn_dir)) { 
            return;
        }

        // View
        if ($type == 'views') { 
            $this->doSvnFile(SITE_PATH . '/views/html/' . $key . '.html', "$svn_dir/views/html/$key.html");
            $this->doSvnFile(SITE_PATH . '/views/php/' . $key . '.php', "$svn_dir/views/php/$key.php");
        } elseif ($type == 'http_controllers') { 
            $this->doSvnFile(SITE_PATH . '/src/HttpControllers/' . $key . '.php', "$svn_dir/share/HttpControllers/$key.php");
        }

    }

    /**
     * Check and transfer SVN file as needed
     */
    private function doSvnFile(string $local_file, string $svn_file):void
    {

        // Initial checks
        if (!file_exists($local_file)) { 
            return;
        }

        // Create parent dir, if needed
        if (!is_dir(dirname($svn_file))) { 
            mkdir(dirname($svn_file), 0755, true);
        }

        // Process, as needed
        if (is_link($local_file) && readlink($local_file) == $svn_file) { 
            return;
        } elseif (file_exists($svn_file)) { 
            unlink($svn_file);
        }

        // Rename and create symlink
        rename($local_file, $svn_file);
        symlink($svn_file, $local_file);
    }

}


