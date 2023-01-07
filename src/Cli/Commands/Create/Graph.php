<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\{CliCommandInterface, GraphInterface};
use Apex\App\Attr\Inject;
use redis;

/**
 * Create graph
 */
class Graph implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        $alias = $this->convert->case(($args[1] ?? ''), 'title');

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/Graphs/$alias.php")) { 
            $cli->error("The data table already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('graph', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Add to redis
        $class_name = $this->opus_helper->pathToNamespace($files[0]);
        $this->redis->sadd('config:interfaces:' . GraphInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new graph which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Graph',
            usage: 'create graph <PKG_ALIAS> <ALIAS>',
            description: 'Create new graph component.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create graph within.');
        $help->addParam('alias', 'The alias / filename of the graph to create.');
        $help->addExample('./apex create graph my-shop invoices');

        // Return
        return $help;
    }

}


