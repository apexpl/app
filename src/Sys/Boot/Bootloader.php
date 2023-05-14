<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Boot;

use Apex\App\Base\Client\ClientInfo;
use Nyholm\Psr7Server\ServerRequestCreator;
use Apex\Container\Interfaces\ApexContainerInterface;
use Psr\Http\Message\{ServerRequestInterface, UriInterface};
use Apex\App\Exceptions\{ApexBootloaderException, ApexInvalidArgumentException};
use Apex\Db\Interfaces\DbInterface;
use Apex\Armor\Auth\AuthSession;
use Apex\Armor\Interfaces\ArmorUserInterface;
use \Doctrine\ORM\EntityManager;
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

    // Database properties
    protected ?object $eloquent = null;
    protected ?EntityManager $doctrine = null;

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
        $this->setRequest();

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
    public function setRequest(?ServerRequestInterface $request = null):void
    {

        // Generate server request, if one not specified
        if ($request === null) {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);
            $this->request = $creator->fromGlobals();
        } else {
            $this->request = $request;
        }

        // Set inputs
        $this->inputs = [
            'get' => $this->request->getQueryParams() ?? [],
            'post' => $this->request->getParsedBody() ?? [],
            'cookie' => $this->request->getCookieParams() ?? [],
            'files' => $this->request->getUploadedFiles() ?? [],
            'server' => $this->request->getServerParams() ?? [],
            'path_params' => []
        ];

        // Set addl properties
        $this->content_type = $this->request->getHeader('content-type')[0] ?? '';
        $this->cntr->set(UriInterface::class, $this->request->getUri());
        $this->cntr->set(ServerRequestInterface::class, $this->request);
        $this->path = $this->request->getUri()->getPath();
    }

    /**
     * Boot Eloquent
     */
    public function bootEloquent():object
    {

        // Check if already booted
        if ($this->eloquent !== null) {
            return $this->eloquent;
        }

        $db = $this->cntr->get(DbInterface::class);
        $connection = \Apex\Db\Wrappers\Eloquent::init($db);
        $connection->bootEloquent();
        $connection->setAsGlobal();

        // Return
        $this->eloquent = $connection;
        return $this->eloquent;
    }

    /**
     * Boot Doctrine
     */
    public function bootDoctrine():EntityManager
    {

        // Check if already booted
        if ($this->doctrine !== null) {
            return $this->doctrine;
        }

        // Get entity dirs
        $redis = $this->cntr->get(redis::class);
        $entity_dirs = $redis->smembers('config:doctrine_entity_classes');

        // Boot Doctrine
        $db = $this->cntr->get(DbInterface::class);
        $this->doctrine = \Apex\Db\Wrappers\Doctrine::init($db, $entity_dirs);

        // Return
        return $this->doctrine;
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

