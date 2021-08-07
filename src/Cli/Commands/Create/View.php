<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Container;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Pkg\Helpers\Registry;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create view
 */
class View implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        $uri = preg_replace("/\.html$/", "", trim(strtolower(($args[1] ?? '')), '/'));

        // Check
        if ($uri == '' || !filter_var('https://domain.com/' . $uri, FILTER_VALIDATE_URL)) { 
            $cli->error("Invalid uri specified, $uri");
            return;
        } elseif (file_exists(SITE_PATH . "/views/html/$uri.html")) { 
            $cli->error("The view already exists with uri, $uri");
            return;
        }

        // Get parent namespace
        $parts = explode('/', $uri);
        $alias = array_pop($parts);
        $parent_nm = count($parts) > 0 ? "\\" . implode("\\", $parts) : '';

            // Build view
        list($dirs, $files) = $this->opus->build('view', SITE_PATH, [
            'uri' => $uri, 
            'alias' => $alias, 
            'parent_namespace' => $parent_nm
        ]);

        // Add to registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $registry->add('views', $uri);

        // Success message
        $cli->success("Successfully created new view for URI $uri, and files are now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create View',
            usage: 'create view <PKG_ALIAS> <URI>',
            description: 'Create a new view.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to register the view to.');
        $help->addParam('uri', 'The URI of the new view, as will be viewed within the web browser and placed relative to the /views/html/ directory.');
        $help->addExample('./apex create view my-shop admin/products/add');

        // Return
        return $help;
    }

}


