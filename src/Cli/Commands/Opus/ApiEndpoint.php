<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Base\Router\RouterConfig;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create API endpoint
 */
class ApiEndpoint implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(OpusHelper::class)]
    private OpusHelper $helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(RouterConfig::class)]
    private RouterConfig $router_config;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['route']);
        $filename = trim(($args[0] ?? ''), '/');
        $filename = $this->helper->parseFilename($filename);
        $route = $opt['route'] ?? '';

        // Perform checks
        if (file_exists(SITE_PATH . '/' . $filename)) { 
            $cli->error("File already exists at, $filename");
            return;
        }

        // Create parent directory, if needed
        $full_path = SITE_PATH . '/' . $filename;
        if (!is_dir(dirname($full_path)) && $cli->getConfirm("Parent directory does not exist at, " . dirname($full_path) . ".  Would you like to create it?", 'y') === true) { 
            mkdir(dirname($full_path), 0755, true);
        } elseif (!is_dir(dirname($full_path))) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Build
        $file = $this->opus->buildClass('api_endpoint', $filename, '', SITE_PATH);

        // Get pkg alias
        $pkg_alias = null;
        if (preg_match("/^src\/(.+?)\//", $file, $m)) { 
            $pkg_alias = $this->convert->case($m[1], 'lower');
        }

        // Add route, if needed
        if ($route != '') { 
            $this->router_config->addRoute($route, 'RestApi');
            if ($pkg_alias !== null) {
                $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
                $registry->add('routes', $route, 'RestApi');
            }
        }

        // Success message
        $cli->success("Successfully created new API endpoint which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate API Endpoint',
            usage: 'opus api-endpoint <FILENAME> [--route=]',
            description: 'Generate a new API endpoint class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new API endpoint class, relative to the /src/ directory.');
        $help->addFlag('--route', 'Optional route and if specified will add a new route to the /boot/routes.yml file.');
        $help->addExample('./apex opus api-endpoint MyShop/Api/Invoices/List');

        // Return
        return $help;
    }


}


