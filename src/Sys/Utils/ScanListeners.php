<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\ListenerInterface;
use redis;

/**
 * Scan workers
 */
class ScanListeners
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Scan
     */
    public function scan():void
    {

        // Purge
        $this->purge();

        // Go through files
        $files = $this->io->parseDir(SITE_PATH . '/src');
        foreach ($files as $file) { 

            // Check for .php
            if (!str_ends_with($file, '.php')) { 
                continue;
            }

            // Load object
            $class_name = "\\" . $this->opus_helper->pathToNamespace($file);
            $obj = new \ReflectionClass($class_name);

            // Skip, if not listener
            if (!$obj->implementsInterface(ListenerInterface::class)) { 
                continue;
            } elseif (!$prop = $obj->getProperty('routing_key')) { 
                continue;
        } elseif (!preg_match("/App\\\\(.+?)\\\\/", $class_name, $match)) { 
            continue;
        }
        $pkg_alias = $this->convert->case($match[1], 'lower');

            // Format routing key
            $parts = array_map(fn ($part) => $this->convert->case($part, 'lower'), explode('.', $prop->getDefaultValue()));
            $routing_key = implode('.', $parts);

            // Add to redis
            $this->redis->hset('config:listeners:' . $routing_key, $pkg_alias, $class_name);
        }

    }

    /**
     * Purge
    */
    private function purge():void
    {

        $keys = $this->redis->keys('config:listeners:*');
        foreach ($keys as $key) { 
            $this->redis->del($key);
        }

    }

}


