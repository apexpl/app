<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

use Apex\Svc\Container;
use Apex\App\Base\Model\BaseModel;
use DateTime;

/**
 * Repository
 */
class LocalRepo
{

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Constructor
     */
    public function __construct(
        private string $host, 
        private string $http_host, 
        private string $svn_host, 
        private string $staging_host,
        private string $alias, 
        private string $name
    ) { 

    }

    /**
     * Get host
     */
    public function getHost():string
    {
        return $this->host;
    }

    /**
     * Get http host
     */
    public function getHttpHost():string
    {
        return $this->http_host;
    }

    /**
     * Get svn host
     */
    public function getSvnHost():string
    {
        return $this->svn_host;
    }

    /**
     * Get staging host
     */
    public function getStagingHost():string
    {
        return $this->staging_host;
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
     * Get API url
     */
    public function getApiUrl(string $path = ''):string
    {

        if (preg_match("/^(.+)\:443/", $this->host, $match)) { 
            $url = 'https://' . $match[1] . '/api/enduro/';
        } else { 
            $url = 'http://' . $this->host . '/api/enduro/';
        }
        $url .= trim($path, '/');

        // Return
        return $url;
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        // Set vars
        $vars = [
            'host' => $this->host, 
            'http_host' => $this->http_host, 
            'svn_host' => $this->svn_host, 
            'staging_host' => $this->staging_host,
            'name' => $this->name
        ];

        // Return
        return $vars;
    }

}

