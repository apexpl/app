<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Boot;

use Apex\App\Sys\ClientInfo;
use Nyholm\Psr7Server\ServerRequestCreator;
use Apex\Container\Interfaces\ApexContainerInterface;
use Psr\Http\Message\{ServerRequestInterface, UriInterface};
use Apex\App\Exceptions\{ApexBootloaderException, ApexInvalidArgumentException};
use Apex\Db\Interfaces\DbInterface;
use Apex\Armor\Auth\AuthSession;
use Apex\Armor\Interfaces\ArmorUserInterface;
use redis;


/**
 * Boot loader for Apex, helps initialize the request, sanitize inputs, et tal.
 */
class Bootloader extends RequestInputs
{

    // Properties
    protected ApexContainerInterface $cntr;
    protected ServerRequestInterface $request;
    protected ClientInfo $client;
    protected ?AuthSession $session = null;
    protected ?ArmorUserInterface $user = null;
    protected ?array $boot_config = [];
    protected string $content_type = '';
    protected string $path;
    protected bool $path_is_locked = false;

    /**
     * Load request
     */
    protected function bootload()
    {

        // Initialize
        $this->initialize();

        // Build DI container
        $this->cntr = Container::build();

        // Load configuration
        $this->loadConfigVars();

        // Generate PSR7 compliant http request
        $this->generateServerRequest();

        // Get client info
        $this->client = new ClientInfo();
    }

    /**
     * Initialize
     */
    private function initialize()
    {

        // Get boot directory
        $obj = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $boot_dir = realpath(dirname($obj->getFileName()) . '/../../boot');

        // Load environment
        require_once("$boot_dir/init/app.php");
        require_once(__DIR__ . '/Common.php');

        // Check if installed, and if not, run installer
        if (!getEnv('redis_host')) { 
            \Apex\App\Sys\Install\Installer::run();
        }
    }

    /**
     * Load config vars
     */
    private function loadConfigVars():void
    {

        // Load config from redis
        $redis = $this->cntr->get(redis::class);
        $this->_config = $redis->hgetall('config');

        // Environment variables to check
        $chk_vars = [
            'instance_name', 
            'mode', 
            'debug_level'
        ];

        // Check various environment variables
        foreach ($chk_vars as $var) { 
            if (!$value = getEnv($var)) { continue; }
            $this->_config['core.' . $var] = $value;
        }

        // Set instance name in container
        if (!isset($this->_config['core.instance_name'])) { 
            $this->_config['core.instance_name'] = 'app1';
        }
        $this->cntr->set('instance_name', $this->_config['core.instance_name']);
    }

    /**
     * Generate PSR7 compliant http request
     */
    private function generateServerRequest():void
    {

        // Sanitize inputs
        $this->inputs = [
            //'get' => filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING) ?? [], 
            //'post' => filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING) ?? [], 
            //'cookie' => filter_input_array(INPUT_COOKIE, FILTER_SANITIZE_STRING) ?? [], 
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'files' => $_FILES,
            'server' => filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING) ?? [], 
            'path_params' => []
        ];
        $http_headers = function_exists('getAllHeaders') ? getAllHeaders() : [];

        // Init server request creator
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);

        // Check for CLI
        if (php_sapi_name() == "cli") {
            $this->request = $creator->fromGlobals();

        } else { 

            // Create server request
            $this->request = $creator->fromArrays(
                $this->inputs['server'], 
                $http_headers,
                $this->inputs['cookie'], 
                $this->inputs['get'], 
                $this->inputs['post'], 
                $_FILES, 
                fopen('php://input', 'r')
            );
        }

        // Get content type
        $content_type = $this->request->getHeader('content-type');
        $this->content_type = $content_type[0] ?? '';

        // Set addl properties
        $this->cntr->set(UriInterface::class, $this->request->getUri());
        $this->path = $this->request->getUri()->getPath();
    }

    /**
     * Boot Eloquent
     */
    public function bootEloquent():object
    {
        $db = $this->cntr->get(DbInterface::class);
        $connection = \Apex\Db\Wrappers\Eloquent::init($db);
        $connection->bootEloquent();
        $connection->setAsGlobal();
        return $connection;
    }

    /**
     * Set config var
     */
    public function setConfigVar(string $key, string | int | float $value):void
    {

        // Check format
        if (!preg_match("/^(.+?)\.(.+)$/", $key, $m)) { 
            throw new ApexInvalidArgumentException("Invalid configuration variable key, $key");
        }
        $this->_config[$key] = $value;

        // Update redis
        $redis = $this->cntr->get(redis::class);
        $redis->hset('config', $key, $value);

        // Update SQL database
        $db = $this->cntr->get(DbInterface::class);
        $db->update('internal_config', ['value' => $value], "package = %s AND alias = %s", $m[1], $m[2]);
    }


}

