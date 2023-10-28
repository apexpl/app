<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\Opus\Opus;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Base\Router\RouterConfig;
use Apex\App\Pkg\Gpt\Models\ViewInfoModel;

/**
 * GPT - View
 */
class GptView extends GptClient
{

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(RouterConfig::class)]
    private RouterConfig $router_config;

    // Properties
    private array $chat;

    /**
     * Generate
     */
    public function generate(string $pkg_alias, ?string $message = null):array
    {

        // Initialize chat
        $this->chat = $this->initChat($pkg_alias, $message);
        $this->chat[] = ['role' => 'user', 'content' => "List minimal number of menus / views that need to be created, one per-line, formatted as '[AREA]/[ALIAS]' where '{AREA]' is either 'public' or 'members' if for the member's area, and '[ALIAS]' is slug of the view.  Any dynamic variables within the URI should be lowercase, prefixed with a colon, and have no suffix.\n"];
        echo "\nDetermining additional views to generate... ";

        // Get views to geneate
        $views = $this->send('', $this->chat);
        $views = explode("\n", $views);

        // Check for zero views
        if (count($views) == 0) {
            echo "none found.\n";
            return [];
        }
        echo "done.\n";

        // Send header
        $this->cli->sendHeader("Views");
        $this->cli->send("Below lists all views that have been determined need to be created.  Enter a comma delimited list of views to generate (eg. 1,3), or leave blank and press enter to accept the default select.\n\n");

        // Display view options
        list($x, $sel, $options) = [1, [], []];
        foreach ($views as $view) {
            $view = preg_replace("/^public\//", "", $view);
            if ($view == '') {
                continue;
            }
            $options[(string) $x] = $view;
            echo "    [$x] /$view\n";
            $sel[] = $x;
        $x++; }
        $this->cli->send("\n");

        // Get input
        $def = implode(", ", $sel);
        $input = $this->cli->getInput("Generate Views [$def]: ", $def);

        // Gather views to generate
        $views = [];
        foreach (explode(',', $input) as $x) {
            $route = $options[trim((string) $x)];
            $uri = $route;

            if (preg_match("/\:(.+?)\//", $route)) {
                $uri = preg_replace("/\:(.+?)\//", "", $route);
            } elseif (str_contains($route, ':')) {
                $uri = preg_replace("/\:.+$/", "_details", $route);
            }
            $views[$uri] = $route;
        }

        // Add selected to chat
        $this->chat[] = ['role' => 'system', 'content' => "The following views will be generated, one view per-line:\n\n" . implode("\n", array_values($views))];

        // Generate views
        $files = [];
        foreach ($views as $uri => $route) {
            echo "Generating view '$route'... ";
            $new_files = $this->generateSingleView($pkg_alias, $uri, $route);
            array_push($files, ...$new_files);
            echo "done.\n";
        }

        // Return
        return $files;
    }

    /**
     * Generate single view
     */
    public function generateSingleView(string $pkg_alias, string $uri, string $route):array
    {

        // Create view
        list($alias, $parent_nm, $files) = $this->create($pkg_alias, $uri, $route);

        // Get view info
        $info = $this->getViewInfo($pkg_alias, $uri, $alias, $route, $parent_nm, $files);
        $layout_obj = $this->cntr->make($info->layout_class);

        // Generate .html file
        $layout_obj->generateHtml($info);

        // Generate .php
        $layout_obj->generatePhp($info);

        // Return
        return $files;
    }
    /**
     * Create view
     */
    private function create(string $pkg_alias, string $uri, string $route):array
    {

        // Check
        if ($uri == '' || !filter_var('https://domain.com/' . $uri, FILTER_VALIDATE_URL)) { 
            $this->cli->error("Invalid uri specified, $uri");
            return [];
        } elseif (file_exists(SITE_PATH . "/views/html/$uri.html")) { 
            $this->cli->error("The view already exists with uri, $uri");
            return [];
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
        $registry->add('views', ltrim($uri, '/'));

        // Add route definition, if route defined
        if ($route != '' && $route != $uri) {
            $http_controller = str_starts_with($uri, 'members/') ? 'MembersArea' : 'PublicSite';
            $this->router_config->addRoute($route, $http_controller);
            $registry->add('routes', $route, $http_controller);
        }

        // Return
        return [$alias, $parent_nm, $files];
    }

    /**
     * Get view info
     */
    private function getViewInfo(string $pkg_alias, string $uri, string $alias, string $route, string $parent_nm, array $files):ViewInfoModel
    {

        // Format must uri
        $msg_uri = preg_match("/^(members|admin)/", ltrim($uri, '/')) ? $uri : 'public/' . ltrim($uri, '/');
        $dbtable = $this->getDatabaseTable($pkg_alias, $msg_uri);
        $layout = $this->getViewLayout($uri, $dbtable);
        //$components = $this->send("Generating the view 'msg_$uri', list the components that should be included within the view, Respond with one component per-line, in placement order from top to bottom, and no additional text or comments.  one per-line.  Available components are:\n\n" . implode("\n", $this->component_types) . "\n", $this->chat);
        $components = '';

        // Get view chat
        $view_chat = $this->chat;
        $view_chat[] = ['role' => 'system', 'content' => "Generating the view '$uri' which pertains to the database table '$dbtable', has the layout '$layout' and contains the components: '" . str_replace("\n", ", ", $components) . "'."];
        $description = $this->send("Write a 1 - 3 sentence description of the view to simplistically describe the page to the general public at large.", $view_chat);

        // Get info
        $info = new ViewInfoModel(
            pkg_alias: $pkg_alias,
            parent_namespace: $parent_nm,
            alias: $alias,
            route: $route,
            uri: $uri,
            layout_class: "Apex\\App\\Pkg\\Gpt\\ViewLayouts\\" . $layout,
            components: explode("\n", $components),
            dbtable: $dbtable,
            description: $description,
            files: $files
        );

        // Return
        return $info;
    }

    /**
     * Get view layout
     */
    private function getViewLayout(string $uri, string $dbtable):string
    {

        // Get layout
        $uri = ltrim($uri, '/');
        $msg_uri = preg_match("/^(members|admin)/", $uri) ? $uri : 'public/' . $uri;
        $types = str_contains($uri, ':') ? $this->view_types_single : $this->view_types_multi;

        // Create type options
        $type_options = '';
        foreach ($types as $num => $name) {
        $type_options .= "    [$num] $name\n";
        }

        // Get layout
        $layout = $this->send("Taking above into context and the generation of the view with URI '$uri' using the database table '$dbtable', select the layout to use from the below options.  Only respond with the number between the [ and ] brackets, and not the full text of the option.  Only respond with a single integer.  Layout options, one per-line are:\n\n$type_options\n");
        $layout = trim(preg_replace("/\D/", "", $layout));
        $layout_class = match ($layout) {
            '1' => 'ForeachLoop',
            '2' => 'ListDataTable',
            '3' => 'ListDataTableForm',
            '4' => 'EnumTabControl',
            '6' => 'ItemDetails',
            '6' => 'SingleForm',
            default => 'Blank'
        };

        // Return
        return $layout_class;
    }

}


