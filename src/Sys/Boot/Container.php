<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Boot;

use Apex\Container\Interfaces\ApexContainerInterface;
use Apex\Db\Interfaces\DbInterface;
use Apex\App\Interfaces\RouterInterface;
use Apex\Debugger\Interfaces\DebuggerInterface;
use Apex\Cluster\Interfaces\{BrokerInterface, ReceiverInterface};
use Apex\Mercury\Interfaces\{EmailerInterface, SmsClientInterface, FirebaseClientInterface, WsClientInterface};
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Apex\Mercury\SMS\NexmoConfig;
use Apex\App\Adapters\ClusterAdapter;
use Apex\Syrus\Interfaces\TagInterface;
use redis;

/**
 * Container builder
 */
class Container
{

    /**
     * Build container
     */
    public static function build():ApexContainerInterface
    {

        // Get container items
        $items = require(SITE_PATH . '/boot/container.php');

        // Load container
        $cntr = call_user_func($items[ApexContainerInterface::class]);

        // Load initial items into container
        $cntr->buildContainer('', $items);

        // Set system items
        $cntr = self::setSystemItems($cntr);

        // Mark items as services
        $cntr = self::markServices($cntr);

        // Define necessary aliases
        $cntr = self::defineAliases($cntr, $items);

        // Set cluster callbacks
        $cntr = self::bootCluster($cntr);

        // Mark developer defined services
        $cntr = self::markDeveloperDefinedServices($cntr);

        // Set into Di wrapper
        \Apex\Container\Di::setContainer($cntr);

        // Return
        return $cntr;
    }

    /**
     * Set system items
     */
    private static function setSystemItems(ApexContainerInterface $cntr):ApexContainerInterface
    {

        // Connect to redis
        $redis = \Apex\Svc\Redis::connect();
        $cntr->set(\redis::class, $redis);
        \Apex\Container\Di::setContainer($cntr);

        // Get config variables
        $debug_level = (int) ($redis->hget('config', 'core.debug_level') ?? 3);
        $debug_level = 0;
        if (!$reserved_usernames = $redis->hget('config', 'core.reserved_usernames') ?? '') { 
            $reserved_usernames = '';
        }

        // Check cache
        $cache = $redis->hget('config', 'core.cache') ?? 0;
        if ($cache != 1) {
            $cntr->set(CacheItemPoolInterface::class, null);
            $cntr->set(CacheInterface::class, null);
        }

        // Define items
        $sys_items = [ 
            \Apex\Db\Drivers\mySQL\mySQL::class => \Apex\Db\Drivers\mySQL\mySQL::class,
            \Apex\Db\Drivers\PostgreSQL\PostgreSQL::class => \Apex\Db\Drivers\PostgreSQL\PostgreSQL::class,
            \Apex\Db\Drivers\SQLite\SQLite::class => \Apex\Db\Drivers\SQLite\SQLite::class,
            \Apex\Armor\Armor::class => [\Apex\Armor\Armor::class, ['container_file' => null, 'policy_name' => 'user', 'policy' => null]], 
            \Apex\Armor\Interfaces\AdapterInterface::class => [\Apex\App\Adapters\ArmorAdapter::class], 
            DebuggerInterface::class => [\Apex\Debugger\Debugger::class, ['debug_level' => $debug_level]], 
            \Apex\Cluster\Interfaces\FeHandlerInterface::class => \Apex\App\Adapters\ClusterFeHandler::class,
            ReceiverInterface::class => \Apex\Cluster\Receiver::class, 
            \Apex\Syrus\Interfaces\LoaderInterface::class => \Apex\App\Adapters\SyrusAdapter::class, 
            \Apex\Syrus\Syrus::class => [\Apex\Syrus\Syrus::class, ['container_file' => null]], 
            \Apex\Cluster\Cluster::class => [\Apex\Cluster\Cluster::class, ['container_file' => null, 'redis' => $redis, 'router_file' => SITE_PATH . '/boot/cluster.yml']],  
            \Apex\Svc\View::class => [\Apex\Svc\View::class, []], 
            \Apex\Migrations\Migrations::class => [\Apex\Migrations\Migrations::class, ['container_file' => null]], 
            NexmoConfig::class => [NexmoConfig::class, [
                'api_key' => ($redis->hget('config', 'core.nexmo_api_key') ?? ''), 
                'api_secret' => ($redis->hget('config', 'core.nexmo_api_secret') ?? ''), 
                'sender' => ($redis->hget('config', 'core.nexmo_sender') ?? '')]
            ],
            'armor.maxmind_dbfile' => SITE_PATH . '/boot/GeoLite2-City.mmdb', 
            'armor.reserved_usernames' => explode('::', $reserved_usernames), 
            'syrus.template_dir' => SITE_PATH . '/views', 
            'syrus.site_yml' => SITE_PATH . '/boot/routes.yml', 
            'syrus.theme_uri' => '/themes', 
            'syrus.php_namespace' => "Views", 
            'syrus.enable_autorouting' => true, 
            'syrus.auto_extract_title' => true, 
            'syrus.use_cluster' => true, 
            'syrus.rpc_message_request' => \Apex\Svc\MessageRequest::class, 
            'syrus.tag_namespaces' => self::getTagNamespaces($redis), 
            'migrations.yaml_file' => SITE_PATH . '/boot/migrations.yml'
        ];

        // Set system items into container
        foreach ($sys_items as $item => $value) { 
            $cntr->set($item, $value);
        }

        // Set armor cookie
        if ($domain_name = $redis->hget('config', 'core.domain_name')) {
            $cookie = $cntr->get('armor_cookie');
            $cookie['domain'] = $domain_name;
        $cntr->set('armor.cookie', $cookie);
        }

        // Return
        return $cntr;
    }

    /**
     * Get tag namespaces
     */
    private static function getTagNamespaces(redis $redis):array
    {

        // Get tag namespaces
        $tag_namespaces = [
            "\\Apex\\App\\Base\\Web\\Tags", 
            "\\Apex\\Docs\\SyrusTags",
            "Apex\\Syrus\\Tags"
        ];

        // Add packages with tags registered
        $tag_packages = [];
        $tag_classes = $redis->smembers('config:interfaces:' . TagInterface::class) ?? [];
        foreach ($tag_classes as $class_name) {
            $parts = explode("\\", $class_name);
            if (isset($parts[3]) && $parts[2] == 'Opus' && $parts[3] == 'Tags' && !in_array($parts[1], $tag_packages)) {
                $tag_namespaces[] = "App\\" . $parts[1] . "\\Opus\\Tags";
            }
        }

        // Return
        return $tag_namespaces;
    }

    /**
     * Mark items as services
     */
    private static function markServices(ApexContainerInterface $cntr):ApexContainerInterface
    {

        // Define services
        $services = [
            DbInterface::class, 
            \Apex\Db\Drivers\mySQL\mySQL::class,
            \Apex\Db\Drivers\PostgreSQL\PostgreSQL::class,
            \Apex\Db\Drivers\SQLite\SQLite::class,
            RouterInterface::class, 
            LoggerInterface::class,
            CacheItemPoolInterface::class, 
            CacheInterface::class,
            BrokerInterface::class, 
            HttpClientInterface::class, 
            DebuggerInterface::class, 
            \Apex\Cluster\Interfaces\FeHandlerInterface::class, 
            ReceiverInterface::class, 
            \Apex\Armor\Interfaces\AdapterInterface::class, 
            \Apex\Syrus\Interfaces\LoaderInterface::class, 
            NexmoConfig::class, 
            \Apex\Armor\Armor::class, 
            \Apex\Cluster\Cluster::class, 
            \Apex\Cluster\Dispatcher::class, 
            \Apex\Cluster\Listener::class, 
            \Apex\Syrus\Syrus::class, 
            \Apex\Svc\Cache::class, 
            \League\Flysystem\Filesystem::class,
            \Apex\Svc\View::class, 
            \Apex\Svc\Convert::class, 
            \Apex\Migrations\Migrations::class, 
            \Apex\App\Adapters\MigrationsConfig::class,
            EmailerInterface::class,
            SmsClientInterface::class,
            FirebaseClientInterface::class,
            WsClientInterface::class
        ];

        // Mark items as services
        foreach ($services as $class_name) { 
            if (!$cntr->markItemAsService($class_name)) { 
                //echo $cntr->getFailReason() . "<br />\n"; exit;
            }
        }

        // Return
        return $cntr;

    }

    /**
     * Define aliases
     */
    private static function defineAliases(ApexContainerInterface $cntr, array $items):ApexContainerInterface
    {

        // Set aliases
        $aliases = [ 
            \Apex\Svc\App::class => \Apex\App\App::class, 
            \Apex\Svc\Db::class => $items[DbInterface::class], 
            \Apex\Svc\Logger::class => LoggerInterface::class, 
            \Apex\Svc\Debugger::class => DebuggerInterface::class,
            \Apex\Svc\Filesystem::class => \League\Flysystem\Filesystem::class,
            \Apex\Svc\Cache::class => CacheInterface::class,
            \Apex\Svc\Psr6Cache::class => CacheItemPoolInterface::class,
            \Apex\Svc\Emailer::class => EmailerInterface::class,
            \Apex\Svc\Firebase::class => FirebaseClientInterface::class, 
            \Apex\Svc\HttpClient::class => HttpClientInterface::class, 
            \Apex\Svc\Container::class => ContainerInterface::class, 
            \Apex\Svc\Convert::class => \Apex\App\Sys\Utils\Convert::class,  
            \Apex\Svc\Dispatcher::class => \Apex\Cluster\Dispatcher::class, 
            \Apex\Migrations\Config::class => \Apex\App\Adapters\MigrationsConfig::class,
            \Apex\Svc\SmsClient::class => SmsClientInterface::class,
            \Apex\Svc\WsClient::class => WsClientInterface::class
        ];

        // Mark aliases
        foreach ($aliases as $item => $alias) { 
            $cntr->addAlias($item, $alias);
        }
        $cntr->addAlias(\Apex\Syrus\Syrus::class, \Apex\Svc\View::class, false);

        // Return
        return $cntr;
    }

    /**
     * Boot cluster
     */
    private static function bootCluster(ApexContainerInterface $cntr):ApexContainerInterface
    {

        // Load adapter
        $adapter = new ClusterAdapter(
            new \Apex\App\Sys\Utils\Convert(),
            $cntr->get(redis::class)
        );

        // Set callbacks
        $cntr->set('cluster.custom_router', [$adapter, 'lookupRoute']);
        $cntr->set('cluster.fe_handler_callback', [$adapter, 'handleFrontEndCallback']);
        $cntr->set('cluster.prepare_msg_handler', [$adapter, 'handleFrontEndCallback']);
//'' => function (eventMessageInterface $msg) { }, 

        $cntr->set('cluster.timeout_handler', [$adapter, 'timeout']);

        // Return
        return $cntr;
    }

    /**
     * Mark developer defined services
     */
    private static function markDeveloperDefinedServices(ApexContainerInterface $cntr):ApexContainerInterface
    {

        // Get redis keys
        $redis = $cntr->get(redis::class);
        $services = $redis->hgetall('config:services') ?? [];

        // Go through services
        foreach ($services as $class_name => $pkg_alias) { 

            // Check class exists
            if (!class_exists($class_name)) { 
                continue;
            }
            $cntr->markItemAsService($class_name);
        }

        // Return
        return $cntr;
    }

}



