<?php
declare(strict_types = 1);

namespace Apex\App\Base\Router;

use Apex\Svc\{Container, App};
use Apex\App\Interfaces\{RouterInterface, RouterResponseInterface};
use Psr\Http\Message\ServerRequestInterface;
use Apex\App\Exceptions\{ApexYamlException, ApexRouterException};
use Apex\App\Attr\Inject;

/**
 * Router
 */
class Router implements RouterInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(App::class)]
    private App $app;

    /**
     * Lookup route
     */
    public function lookup(ServerRequestInterface $request):RouterResponseInterface
    {

        // Load routes
        $routes = $this->getRoutes($request->getUri()->getHost());

        // Initialize Variables
        $http_controller = $routes['default'] ?? 'PublicSite';
        $path = ltrim($request->getUri()->getPath(), '/');

        $params = [];
        $match_num = 0;

        // Go through routes
        foreach ($routes as $chk_path => $controller) { 
            $chk_path = ltrim($chk_path, '/');
            list($full_match, $param_keys) = [false, []];

            // Check for full match
            if (preg_match("/^(.+)\\$$/", $chk_path, $m)) { 
                $chk_path = $m[1];
                $full_match = true;
            }

            // Check for parameters
            if (preg_match("/^(.+?):(.+$)/", $chk_path, $m)) { 
                $chk_path = $m[1];
                $param_keys = array_map(function($p) { return ltrim($p, ':'); }, explode('/', $m[2]));
            }

            // Check for a match
            if ($full_match === true && $chk_path != $path) { 
                continue;
            } elseif ($full_match !== true && !str_starts_with($path, $chk_path)) { 
                continue;
            }

            // Get params, if needed
            if (count($param_keys) > 0) { 
                $vars = explode('/', preg_replace("/^" . str_replace("/", "\\/", $chk_path) . "/", '', $path));
                $path = $chk_path;

                foreach ($param_keys as $key) { 
                    $params[$key] = count($vars) > 0 ? array_shift($vars) : '';
                }
            }

            // Set controller, and break
            $http_controller = $controller;
            break;
        }

        // Load middleware
        $class_name = "\\App\\HttpControllers\\" . $http_controller;

        if (!class_exists($class_name)) { 
            throw new ApexRouterException("Middleware does not exist at $http_controller");
        }
        $http_controller = $this->cntr->make($class_name);

        // Get router response
        $response = new RouterResponse(
            http_controller: $http_controller, 
            path_translated: '/' . $path, 
            params: $params
        );

        // Return 
        return $response;
    }

    /**
     * Get routes
     */
    private function getRoutes(string $host):array
    {

        // Load router file'
        $yaml = $this->app->getRoutesConfig();

        // Get routes
        $routes = $yaml['routes'] ?? [];
        if (count($routes) == 0) { 
            throw new ApexRouterException("No routes exist within /boot/routes.yml file");
        } elseif (!isset($routes['default'])) { 
            throw new ApexRouterException("The /boot/routes.yml file does not contain a 'default' entry, which is required.");
        }

        // Check if multi host
        $first = array_keys($routes)[0];
        if (!is_array($routes[$first])) { 
            $res = $routes;
        } else { 

            // Check for host based route
            $host = preg_replace("/^www\./", '', strtolower($host));
            $res = $routes[$host] ?? $routes['default'];
        }

        // Sort routes
        uksort($res, function ($a, $b) {
            return substr_count($a, '/') >= substr_count($b, '/') ? -1 : 1;
        });

        // Return
        return $res;
    }

}

