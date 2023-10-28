<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Base\Router\RouterConfig;

/**
 * GPT - Rest API
 */
class GptRestApi extends GptClient
{

    #[Inject(RouterConfig::class)]
    private RouterConfig $router_config;

    // Properties
    private array $new_routes = [];

    /**
     * Generate
     */
    public function generate(string $pkg_alias, string $message):array
    {

        // Initialize
        $files = [];

        // Get chat
        $this->chat[] = ['role' => 'system', 'content' => "Description of REST API endpoints to generate is:\n\n$message\n"];
        $res = $this->send("Determine the minimal number of API endpoints to generate using the above description.  Respond with one endpoint per-line with the format '[HTTP_METHOD] [URI] [ONE_LINE_DESCRIPTION]', with the URI prefixed with '/api/$pkg_alias'.  Dynamic variables within the URI should be lowercased and prefixed with a colon, but nothing else.", $this->chat);

        // Check for zero
        if (count(explode("\n", $res)) == 0) {
            return [];
        }

        // Send header
        $this->cli->sendHeader('REST API Endpoints');
        $this->cli->send("Below lists the REST API endpoints that were determined need to be generated.  Define a comma delimited list (eg. 1,3) of the endpoints you would like generated, or leave blank to accept the default and have all endpoints generated.\n\n");

        // Get options
        list($x, $sel) = [1, []];
        foreach (explode("\n", $res) as $line) {
            list($method, $uri, $desc) = explode(" ", $line, 3);
            $options[(string) $x] = strtoupper($method) . ' ' . $uri;
            echo "    [$x] " . $options[(string) $x] . " ($desc)\n";
        $sel[] = (string) $x;
        $x++; }
        $this->cli->send("\n");

        // Get input
        $sel = implode(", ", $sel);
        $input = $this->cli->getInput("API Endpoints to Generate [$sel]: ", $sel);

        // Gather endpoints
        $endpoints = [];
        foreach (explode(",", $input) as $x) {
            list($method, $uri) = explode(" ", $options[trim((string) $x)], 2);
            $dyn_var = preg_match("/\:(.+)$/", $uri, $match) ? $match[1] : null;
            $uri = preg_replace("/\/\:.+$/", "", $uri);

            $endpoints[$uri][$method][] = $dyn_var;
            //s$endpoints[$uri][] = [$method, $dyn_var];
        }

        // Generate endpoints
        foreach ($endpoints as $uri => $methods) {
            $files[] = $this->generateEndpoint($pkg_alias, $uri, $methods);
        }

        // Add routes as needed
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        foreach (array_keys($this->new_routes) as $route) {
            $this->router_config->addRoute($route, 'RestApi');
            $registry->add('routes', $route, 'RestApi');
        }


        // Return
        return $files;
    }

    /**
     * Generate endpoint
     */
    private function generateEndpoint(string $pkg_alias, string $uri, array $methods):string
    {

        // Get table name
        $dbtable = $this->getDatabaseTable($pkg_alias, $uri, $this->chat);
        $model_class = $this->getModelByTable($pkg_alias, $dbtable);
        $obj = new \ReflectionClass($model_class);

        // Generate methods
        $code = '';
        foreach ($methods as $method => $vars) {
            $has_null = in_array(null, $vars) ? true : false;
            $dyn_var = null;

            // Get dyn_var, if needed
            if (count($vars) > 1) {
                $vars = array_filter($vars, function ($var) { return $var === null ? false : true; });
                $dyn_var = reset($vars);
                $this->new_routes[$uri . '/:' . $dyn_var] = 1;
            }

            // GEt code
            $method_name = strtolower($method);
            $code .= $this->$method_name($pkg_alias, $dbtable, $obj, $has_null, $dyn_var);
        }

        // Save file
        $filename = $this->save($pkg_alias, $uri, $code, $obj);
        return $filename;
    }

    /**
     * GET
     */
    private function get(string $pkg_alias, string $dbtable, \ReflectionClass $obj, bool $has_null, ?string $dyn_var = null):string
    {

        // Initialize
        $list_and_get = false;
        $prefix = '        ';
        if ($has_null === true && $dyn_var !== null) {
            $list_and_get = true;
            $prefix = '            ';
        }

        // Start code
        $code = "\n    /**\n     * Get\n     */\n";
        $code .= "    public function get(ServerRequestInterface \$request, RequestHandlerInterface \$app):ApiResponse\n    {\n\n";
        if ($list_and_get === true) {
            $code .= "        // List all items\n";
            $code .= "        if (!\$app->pathParam('$dyn_var')) {\n\n";
        }

        // List all items
        if ($has_null === true) {
            $code .= $prefix . "// Get all items\n";
            $code .= $prefix . "\$data = [];\n";
            $code .= $prefix . "\$items = " . $obj->getShortName() . "::all('id', 'asc');\n";
            $code .= $prefix . "foreach (\$items as \$item) {\n";
            $code .= $prefix . "    \$data[] = \$item->toArray();\n";
            $code .= $prefix . "}\n\n";
        }

        // Add else, if needed
        if ($list_and_get === true) {
            $code .= "        // Single item\n";
            $code .= "        } else {\n\n";
        }

        // Single item
        if ($dyn_var !== null) {
            $code .= $prefix . "// Get item\n";
            $code .= $prefix . "if (!\$item = " . $obj->getShortName() . "::whereId(\$app->pathParam('$dyn_var'))) {\n";
            $code .= $prefix . "    return new ApiResponse(404, [], \"No item exists with that id#\");\n";
            $code .= $prefix . "}\n";
            $code .= $prefix . "\$data = \$item->toArray();\n";
        }

        // Finish
        if ($list_and_get === true) {
            $code .= "        }\n";
        }
        $code .= "\n        // Return\n";
        $code .= "        return new ApiResponse(200, \$data);\n";
        $code .= "    }\n\n";

        // Return
        return $code;
    }

    /**
     * Post
     */
    private function post(string $pkg_alias, string $dbtable, \ReflectionClass $obj, bool $has_null, ?string $dyn_var = null):string
    {

        // Get required fields
        $req_fields = [];
        $cols = $this->db->getColumnDetails($dbtable);
        foreach ($cols as $col_name => $vars) {
            if ($vars['is_primary'] === true || ($vars['allow_null'] === false && $vars['default'] == '')) {
                $req_fields[] = $col_name;
            }
        }
        $req_fields = count($req_fields) == 0 ? null : implode("', '", $req_fields);

        // Start code
        $code = "\n    /**\n     * Post\n     */\n";
        $code .= "    public function post(ServerRequestInterface \$request, RequestHandlerInterface \$app):ApiResponse\n    {\n\n";

        // Required fields
        if ($req_fields !== null) {
            $code .= "        // Check Required\n";
            $code .= "        if (!\$this->checkRequired('$req_fields')) {\n";
            $code .= "            return \$this->getResponse();\n";
            $code .= "        }\n\n";
        }

        // Create item
        $code .= "        // Create item\n";
        $code .= "        \$item = " . $obj->getShortName() . "::insertFromPost();\n\n";
        $code .= "        // Return\n";
        $code .= "        return new ApiResponse(200, \$item->toArray());\n";
        $code .= "    }\n\n";

        // Return
        return $code;
    }

    /**
     * Put
     */
    private function put(string $pkg_alias, string $dbtable, \ReflectionClass $obj, bool $has_null, ?string $dyn_var = null):string
    {

        // Get required fields
        $req_fields = [$dyn_var];
        $cols = $this->db->getColumnDetails($dbtable);
        foreach ($cols as $col_name => $vars) {
            if ($vars['is_primary'] === true || ($vars['allow_null'] === false && $vars['default'] == '')) {
                $req_fields[] = $col_name;
            }
        }
        $req_fields = count($req_fields) == 0 ? null : implode("', '", $req_fields);

        // Start code
        $code = "\n    /**\n     * Put\n     */\n";
        $code .= "    public function put(ServerRequestInterface \$request, RequestHandlerInterface \$app):ApiResponse\n    {\n\n";

        // Required fields
        if ($req_fields !== null) {
            $code .= "        // Check Required\n";
            $code .= "        if (!\$this->checkRequired('$req_fields')) {\n"; 
            $code .= "            return \$this->getResponse();\n";
            $code .= "        }\n\n";
        }

        // Get item
        $code .= "        // Get item\n";
        $code .= "        if (!\$item = " . $obj->getShortName() . "::whereId(\$app->pathParam('$dyn_var'))) {\n";
        $code .= "            return new ApiResponse(404, [], \"No item exists with this id#\");\n";
        $code .= "        }\n\n";

        // Update code as needed
        $code .= "        // Update item\n";
        $code .= "        \$cols = \$this->db->getColumnDetails('$dbtable');\n";
        $code .= "        foreach (\$cols as \$col_name => \$vars) {\n";
        $code .= "            if (\$value = \$app->post(\$col_name)) {\n";
        $code .= "                \$item->\$col_name = \$value;\n";
        $code .= "            }\n";
        $code .= "        }\n\n";

        // Finish
        $code .= "        // Save and return\n";
        $code .= "        \$item->save();\n";
        $code .= "        return new ApiResponse(200, \$item->toArray());\n";
        $code .= "    }\n\n";

        // Return
        return $code;
    }

    /**
     * Delete
     */
    private function delete(string $pkg_alias, string $dbtable, \ReflectionClass $obj, bool $has_null, ?string $dyn_var = null):string
    {

        // Start code
        $code = "\n    /**\n     * Delete\n     */\n";
        $code .= "    public function delete(ServerRequestInterface \$request, RequestHandlerInterface \$app):ApiResponse\n    {\n\n";

        // Get item
        $code .= "        // Get item\n";
        $code .= "        if (!\$app->pathParam('$dyn_var')) {\n";
        $code .= "            return new ApiResponse(400, [], \"No '$dyn_var' path parameter specified.\");\n";
        $code .= "        } else if (!\$item = " . $obj->getShortName() . "::whereId(\$app->pathParam('$dyn_var'))) {\n";
        $code .= "            return new ApiResponse(404, [], \"No item exists with this id#\");\n";
        $code .= "        }\n\n";

        // Delete and finish
        $code .= "        // Delete\n";
        $code .= "        \$item->delete();\n";
        $code .= "        return new ApiResponse(200);\n";
        $code .= "    }\n\n";

        // Return
        return $code;
    }
    /**
     * Save file
     */
    private function save(string $pkg_alias, String $uri, string $method_code, \ReflectionCLass $obj):string
    {

        // Get namespace
        $parts = explode("/", trim($uri, '/'));
        array_splice($parts, 0, 2);
        $class_alias = array_pop($parts);

        // Set variables
        $namespace = "App\\" . $this->convert->case($pkg_alias, 'title') . "\\Api";
        foreach ($parts as $part) {
            $namespace .= "\\" . $this->convert->case($part, 'title');
        }
        $code = '';

        // Start code
        $code = "<?php\ndeclare(strict_types = 1);\n\n";
        $code .= "namespace " . $namespace . ";\n\n";
        $code .= "use Apex\\Svc\\Db;\n";
        $code .= "use " . $obj->getName() . ";\n";
        $code .= "use App\\RestApi\\Helpers\\ApiRequest;\n";
        $code .= "use App\\RestApi\\Models\\{ApiResponse, ApiDoc, ApiParam, ApiReturnVar};\n";
        $code .= "use Psr\\Http\\Message\\ServerRequestInterface;\n";
        $code .= "use Psr\\Http\\Server\\RequestHandlerInterface;\n\n";
        $code .= "/**\n * API $class_alias\n */\n";
        $code .= "class " . $this->convert->case($class_alias, 'title') . " extends ApiRequest\n{\n\n";
        $code .= "    #[Inject(Db::class)]\n    private Db \$db;\n\n";
        $code .= $method_code;
        $code .= "}\n\n";

        // Get filename
        $filename = str_replace("\\", "/", preg_replace("/^App\\\\/", "", $namespace));
        $filename = 'src/' . $filename . '/' . $this->convert->case($class_alias, 'title') . '.php';

        // Save and return
        mkdir(dirname($filename), 0755, true);
        file_put_contents(SITE_PATH . '/' . $filename, $code);
        return $filename;
    }

}


