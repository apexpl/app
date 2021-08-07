<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\{Container, Convert};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\Registry;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Exceptions\ApexYamlException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Create http controller
 */
class HttpController implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }

        // Get args
        $opt = $cli->getArgs(['path']);
        $alias = $this->convert->case(($args[1] ?? ''), 'title');
        $path = $opt['path'] ?? '';

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/HttpControllers/$alias.php")) { 
            $cli->error("The HTTP controller already exists with alias, $alias");
            return;
        }

        // Create
        list($dirs, $files) = $this->opus->build('http_controller', SITE_PATH, ['alias' => $alias]);

        // Add to components
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg->getAlias()]);
        $registry->add('http_controllers', $alias);

        // Add route definition, if route defined
        if ($path != '') { 
            $registry->add('routes', $path, $alias);
            $this->addRoute($path, $alias);
        }

        // Success message
        $cli->success("Successfully created new HTTP controller, which is now available at:", $files);
    }

    /**
     * Add route
     */
    private function addRoute(string $uri, string $alias):void
    {

        // Load router file'
        try {
            $yaml = Yaml::parseFile(SITE_PATH . '/boot/routes.yml');
        } catch (ParseException $e) { 
            throw new ApexYamlException("Unable to parse routes.yml YAML file, error: " . $e->getMessage());
        }
        $routes = $yaml['routes'] ?? [];

        // Add route as needed
        if (isset($routes['default']) && is_array($routes['default']) && !isset($routes['default'][$uri])) { 
            $routes['default'][$uri] = $alias;
        } elseif (!isset($routes[$uri])) { 
            $routes[$uri] = $alias;
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

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create HTTP Controller',
            usage: 'create http-controller <PKG_ALIAS> <ALIAS> [--path=]',
            description: 'Create and register a new HTTP controller within the /src/HttpControllers directory'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to register HTTP controller to.');
        $help->addParam('alias', 'The alias / filename of the HTTP controller to create.');
        $help->addFlag('--path', 'If defined, new routes within /boot/routes.yml file will be added.');
        $help->addExample('./apex create http-controller my-shop products --uri product');

        // Return
        return $help;
    }

}


