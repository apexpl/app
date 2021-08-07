<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\{Container, Convert, Db};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Reset redis
 */
class ResetRedis implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(redis::class)]
    private redis $redis;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package list
        if (count($args) > 0) { 
            $packages = $args;
        } else { 
            $packages = array_keys($this->pkg_store->list());
        }

        // Go through packages
        foreach ($packages as $pkg_alias) { 

            // Check for migrate class
            $class_name = "Etc\\" . $this->convert->case($pkg_alias, 'title') . "\\migrate";
            if (!class_exists($class_name)) { 
                continue;
            }

            // Load class, check for resetRedis method
            $obj = $this->cntr->make($class_name);
            if (!method_exists($obj, 'resetRedis')) { 
                continue;
            }

        // Reset redis
        $obj->resetRedis($this->db, $this->redis);
        }

        // Success
        $cli->send("Successfully reset redis on all necessary packages.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Reset Redis',
            usage: 'sys reset-redis [<PKG_ALIAS>]',
            description: 'Reset the redis keys for any or all packages.'
        );

        $help->addParam('pkg_alias', 'Optional package alias to reset redis for.  If not defined, all packages will be reset.');
        $help->addExample('./apex sys reset-redis');

        // Return
        return $help;
    }

}


