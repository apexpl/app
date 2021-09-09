<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

/**
 * Installation image model
 */
class InstallationImage
{

    /**
     * Constructor
     */
    public function __construct(
        private string $alias,
        private string $name,
        private string $version,
        private string $access = 'public',
        private string $description = '',
        private array $packages = [],
        private array $config = []
    ) { 

    }

    /**
     * Get alias
     */
    public function getAlias():string
    {
        return $this->alias;
    }

    /**
     * Get name
     */
    public function getName():string
    {
        return $this->name;
    }

    /**
     * Get version
     */
    public function getVersion():string
    {
        return $this->version;
    }

    /**
     * Get access
     */
    public function getAccess():string
    {
        return $this->access;
    }

    /**
     * Get description
     */
    public function getDescription():string
    {
        return $this->description;
    }

    /**
     * Get packages
     */
    public function getPackages():array
    {
        return $this->packages;
    }

    /**
     * Get config vars
     */
    public function getConfigVars():array
    {
        return $this->config;
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        // Set general vars
        $general = [
            'name' => $this->name,
            'version' => $this->version,
            'access' => $this->access,
            'description' => $this->description
        ];

        // Set vars
        $vars = [
            'general' => $general,
            'packages' => $this->packages,
            'config' => $this->config
        ];

        // Return
        return $vars;
    }

}

