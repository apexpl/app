<?php
declare(strict_types = 1);

namespace Apex\App\Base\Router;

use Apex\Svc\App;
use Apex\App\Exceptions\ApexYamlException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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
        $yaml = $this->app->getRoutesConfig();
        $routes = $yaml['routes'] ?? [];

        // Add route as needed
        if (isset($routes[$host]) && is_array($routes[$host]) && !isset($routes[$host][$path])) { 
            $routes[$host][$path] = $http_controller;
        } elseif (!isset($routes[$path])) { 
            $routes[$path] = $http_controller;
        } else { 
            return;
        }
        $yaml['routes'] = $routes;

        // Set YAML text
        $text = "\n##########\n# Routes\n#\n";
        $text .= "# This file has been auto-generated, but you may modify as desired below.  Please refer to the developer \n";
        $text .= "# documentation for details on the entries within this file.\n##########\n\n";
        $text .= Yaml::dump($yaml, 6);

        // Save YAML file
        file_put_contents(SITE_PATH . '/boot/routes.yml', $text);
    }

}

