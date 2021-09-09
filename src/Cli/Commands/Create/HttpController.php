<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\{Container, Convert};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Base\Router\RouterConfig;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Psr\Http\Server\MiddlewareInterface;
use redis;

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

    #[Inject(RouterConfig::class)]
    private RouterConfig $router_config;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    #[Inject(redis::class)]
    private redis $redis;

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
        $opt = $cli->getArgs(['route']);
        $alias = $this->convert->case(($args[1] ?? ''), 'title');
        $route = $opt['route'] ?? '';

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
        if ($route != '') { 
            $this->router_config->addRoute($route, $alias);
            $registry->add('routes', $route, $alias);
        }

        // Add to redis
        $class_name = $this->opus_helper->pathToNamespace($files[0]);
        $this->redis->sadd('config:interfaces:' . MiddlewareInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new HTTP controller, which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create HTTP Controller',
            usage: 'create http-controller <PKG_ALIAS> <ALIAS> [--route=]',
            description: 'Create and register a new HTTP controller within the /src/HttpControllers directory'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to register HTTP controller to.');
        $help->addParam('alias', 'The alias / filename of the HTTP controller to create.');
        $help->addFlag('--route', 'If defined, new route within /boot/routes.yml file will be added.');
        $help->addExample('./apex create http-controller my-shop products --route product');

        // Return
        return $help;
    }

}


