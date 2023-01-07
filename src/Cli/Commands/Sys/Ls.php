<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Base\Implementors;
use Apex\App\Interfaces\Opus\{CliCommandInterface, CrontabInterface};
use Apex\App\Attr\Inject;
use redis;

/**
 * List services
 */
class Ls implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Implementors::class)]
    private Implementors $implementors;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Check for package
        $pkg_alias = null;
        if (isset($args[1])) { 
            $pkg_alias = $this->convert->case($args[1], 'lower');
        }
        $type = $args[0] ?? '';

        // List necessary classes
        if ($type == 'services') { 
            $this->listServices($cli, $pkg_alias);
        } elseif ($type == 'crontab') { 
            $this->listCrontab($cli, $pkg_alias);
        } else { 
            $cli->error("Invalid type '$type' specified.  Supported values are: services, crontab");
        }

    }

    /**
     * List services
     */
    private function listServices(Cli $cli, ?string $pkg_alias = null):void
    {

        // Organize services
        $services = [];
        $keys = $this->redis->hgetall('config:services') ?? [];
        foreach ($keys as $class_name => $package) { 

            // Skip, if needed
            if ($pkg_alias !== null && $package != $pkg_alias) { 
                continue;
            }
            $servers[$package][] = $class_name;
        }

        // Check for none
        if (count($services) == 0) { 
            $cli->send("There are no developer defined services on the local machine.\r\n\r\n");
            return;
        }

        // Send header
        asort($services);
        $cli->sendHeader('Developer Defined Services');
        $cli->send("Below shows all services available within the container that have been defined by package developrs.\r\n\r\n");

        // Go through services
        foreach ($services as $pkg_alias => $classes) { 

            $cli->send("Package: $pkg_alias\r\n");
            foreach ($classes as $class_name) { 
                $cli->send("    $class_name\r\n");
            }
            $cli->send("\r\n");
        }

    }

    /**
     * List crontab
     */
    private function listCrontab(Cli $cli, ?string $pkg_alias = null):void
    {

        // Get crontab jobs
        $crontab = $this->implementors->getPropertyValues(CrontabInterface::class, 'description');

        // Go through crontab jobs
        $results = [];
        foreach ($crontab as $class_name => $description) { 

            // Parse class name
            $parts = explode("\\", trim($class_name, "\\"));
            if (count($parts) < 4) { 
                continue;
            }

            // Get alias
            $package = $this->convert->case($parts[1], 'lower');
            $alias = $package . '.' . $this->convert->case($parts[4], 'lower');

            // Skip, if needed
            if ($pkg_alias !== null && $pkg_alias === $package) { 
                continue;
            }

            // Add to results
            $results[$alias] = $description;
        }

        // Send array
        $cli->sendArray($results);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'List Developer Defined Services',
            usage: 'sys list (services|crontab) [<pkg_alias>]',
            description: 'Lists all developer defined servers or crontab jobs on the local machine.'
        );
        $help->addParam('type', "The type of classes to list.  Supported values are: services, crontab");
        $help->addParam('pkg_alias', "Optional package alias if you wish to only list classes for a specific package.");
        $help->addExample('./apex sys list services');
        $help->addExample('./apex sys list crontab');

        // Return
        return $help;
    }

}

