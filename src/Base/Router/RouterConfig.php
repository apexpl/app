<?php
declare(strict_types = 1);

namespace Apex\App\Base\Router;

use Apex\Svc\App;
use Apex\App\Exceptions\ApexYamlException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\App\Attr\Inject;

/**
 * Router config
 */
class RouterConfig
{

    #[Inject(App::class)]
    private App $app;

    /**
     * Add route
     */
    public function addRoute(string $path, string $http_controller, string $host = 'default'):void
    {

        // Load router file'
        $yaml = $this->app->getRoutesConfig('routes.yml', true);
        $routes = $yaml['routes'] ?? [];

        // Add route as needed
        if (isset($routes[$host]) && is_array($routes[$host]) && !isset($routes[$host][$path])) { 
            $routes[$host][$path] = $http_controller;
        } elseif (!isset($routes[$path])) { 
            $routes[$path] = $http_controller;
        } else { 
            return;
        }

        // Save file
        $yaml['routes'] = $routes;
        $this->save($yaml);
    }

    /**
     * Remove a route
     */
    public function removeRoute(string $path, string $host = 'default'):void
    {

        // Load router file'
        $yaml = $this->app->getRoutesConfig('routes.yml', true);
        $routes = $yaml['routes'] ?? [];

        // Add route as needed
        if (isset($routes[$host]) && is_array($routes[$host]) && isset($routes[$host][$path])) { 
            unset($routes[$host][$path]);
        } elseif (isset($routes[$path])) { 
            unset($routes[$path]);
        } else { 
            return;
        }

        // Save file
        $yaml['routes'] = $routes;
        $this->save($yaml);
    }

    /**
     * Save yaml file
     */
    private function save(array $yaml):void
    {

        // Set YAML text
        $text = "\n##########\n# Routes\n#\n";
        $text .= "# This file has been auto-generated, but you may modify as desired below.  Please refer to the developer \n";
        $text .= "# documentation for details on the entries within this file.\n##########\n\n";
        $text .= Yaml::dump($yaml, 6);

        // Save YAML file
        file_put_contents(SITE_PATH . '/boot/routes.yml', $text);
    }

}




