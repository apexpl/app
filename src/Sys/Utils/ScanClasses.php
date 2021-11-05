<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\ListenerInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Scan workers
 */
class ScanClasses
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
            $class_name = $this->opus_helper->pathToNamespace($file);
            $obj = new \ReflectionClass($class_name);

            // Get interfaces class implements
            $interfaces = $obj->getInterfaceNames();
            foreach ($interfaces as $interface) { 
                $this->redis->sadd('config:interfaces:' . $interface, $class_name);
            }

            // Check for listener
            if ($obj->implementsInterface(ListenerInterface::class)) { 
                $this->scanListener($obj, $class_name);
                continue;
            }

            // Check for parent_class property
            if (!$prop = $obj->hasProperty('parent_class')) { 
                continue;
            }
            $parent_class = $obj->getProperty('parent_class')->getDefaultValue();

            // Add to redis
            $this->redis->sadd('config:child_classes:' . $parent_class, $class_name); 
        }

    }

    /**
     * Scan listener
     */
    private function scanListener(\ReflectionClass $obj, string $class_name):void
    {

        // Check
        if (!$prop = $obj->getProperty('routing_key')) { 
            return;
        } elseif (!preg_match("/App\\\\(.+?)\\\\/", $class_name, $match)) { 
            return;
        }
        $pkg_alias = $this->convert->case($match[1], 'lower');

        // Format routing key
        $parts = array_map(fn ($part) => $this->convert->case($part, 'lower'), explode('.', $prop->getDefaultValue()));
        $routing_key = implode('.', $parts);

        // Add to redis
        $this->redis->hset('config:listeners:' . $routing_key, $pkg_alias, $class_name);
    }

    /**
     * Purge
    */
    private function purge():void
    {

        foreach (['listeners', 'child_classes', 'interfaces'] as $var) { 

            $keys = $this->redis->keys('config:' . $var . ':*');
            foreach ($keys as $key) { 
                $this->redis->del($key);
            }
        }
    }

}


