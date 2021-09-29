<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\App\Exceptions\{ApexYamlException, ApexAccountsStoreException};
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Abstract store
 */
class AbstractStore
{

    /**
     * Determine config dir
     */
    final public function determineConfDir():string
    {

        // Get dir
        if ($dir = GetEnv('APEX_CONFDIR')) { 
            return rtrim($dir, '/');
        } elseif ($dir = GetEnv('apex_confdir')) { 
            return rtrim($dir, '/');
        } elseif (isset($_SERVER['HOME']) && is_dir($_SERVER['HOME'])) { 
            return rtrim($_SERVER['HOME'], '/') . '/.config/apex';
        } else { 
            throw new ApexAccountsStoreException("Unable to determine location of Apex configuration directory.");
        }

    }

    /**
     * Load YAML file
     */
    final protected function loadYamlFile(string $file):array
    {

        // Load file
        try {
            $yaml = Yaml::parseFile($file);
        } catch (ParseException $e) { 
            throw new ApexYamlException("Unable to parse '$file' YAML file, error: " . $e->getMessage());
        }

        // Return
        return $yaml;
    }

}

