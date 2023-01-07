<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Armor\x509\DistinguishedName;
use Apex\Db\Mapper\ToInstance;
use Symfony\Component\Yaml\Yaml;

/**
 * DN Store
 */
class DistinguishedNamesStore extends AbstractStore
{

    /**
     * Constructor
     */
    public function __construct(
        private string $confdir = ''
    ) { 

        // Get confdir
        if ($this->confdir == '') { 
            $this->confdir = $this->determineConfDir();
        }
        $this->dn_file = $this->confdir . '/dn.yml';

    }

    /**
     * List distinguished names
     */
    public function list():array
    {

        // Check file exists
        if (!file_exists($this->dn_file)) { 
            return [];
        }

        // Load DN file
        $yaml = $this->loadYamlFile($this->dn_file);

        // Go through accounts
        $names = [];
        foreach ($yaml as $alias => $vars) { 
            $names[$alias] = $vars['org_name'] . ' <' . $vars['email'] . '> (' . $vars['locality'] . ', ' . $vars['country'] . ')';
        }

        // Return
        return $names;
    }

    /**
     * Load name
     */
    public function load(string $alias):?DistinguishedName
    {

        // Load file
        $yaml = file_exists($this->dn_file) ? $this->loadYamlFile($this->dn_file) : [];
        if (!isset($yaml[$alias])) { 
            return null;
        }

        // Load and return DN
        return ToInstance::map(DistinguishedName::class, $yaml[$alias]);
    }

    /**
     * Save dn
     */
    public function save(string $name, DistinguishedName $dn):void
    {

        // Get yaml
        $yaml = file_exists($this->dn_file) ? $this->loadYamlFile($this->dn_file) : [];
        $yaml[$name] = [
            'country' => $dn->country, 
            'province' => $dn->province, 
            'locality' => $dn->locality, 
            'org_name' => $dn->org_name, 
            'org_unit' => $dn->org_unit, 
            'email' => $dn->email
        ];

        // Save yaml file
        if (!is_dir(dirname($this->dn_file))) { 
            mkdir(dirname($this->dn_file), 0755, true);
        }
        file_put_contents($this->dn_file, Yaml::dump($yaml));
    }

}

