<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Create listener
 */
class Listener implements CliCommandInterface
{


    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['routing-key']);
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        $alias = $this->convert->case(($args[1] ?? ''), 'title');
        $routing_key = $opt['routing-key'] ?? '';

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Listeners/$alias.php")) { 
            $cli->error("The listener already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('listener', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias,
            'routing_key' => $routing_key
        ]);

        // Add routing key to redis, if needed
        if (preg_match("/^[a-zA-Z0-9_-]+\.[a-zA-z0-9_-]+/", $routing_key)) { 
            $class_name = "\\App\\" . $this->convert->case($pkg_alias, 'title') . "\\Listeners\\" . $this->convert->case($alias, 'title');
            $this->redis->hset('config:listeners:' . $routing_key, $pkg_alias, $class_name);
        }

        // Success message
        $cli->success("Successfully created new listener which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Listener',
            usage: 'create listener <PKG_ALIAS> <ALIAS> [--routing-key=]',
            description: 'Create a new listener.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create listener within.');
        $help->addParam('alias', 'The alias / filename of the listener to create.');
        $help->addFlag('--routing-key', 'Optional routing key to assign listener to.');
        $help->addExample('./apex create listener my-shop users --routing-key users.profile');

        // Return
        return $help;
    }

}


