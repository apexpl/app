<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\{CliCommandInterface, DashboardItemInterface};
use redis;

/**
 * Create dashboard item
 */
class DashboardItem implements CliCommandInterface
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
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/DashboardItems/$alias.php")) { 
            $cli->error("The dashboard item already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('dashboard_item', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Add to redis
        $class_name = $this->opus_helper->pathToNamespace($files[0]);
        $this->redis->sadd('config:interfaces:' . DashboardItemInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new dashboard item which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Dashboard Item',
            usage: 'create dashboard-item <PKG_ALIAS> <ALIAS>',
            description: 'Create new dashboard item component.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create dashboard item within.');
        $help->addParam('alias', 'The alias / filename of the dashboard item to create.');
        $help->addExample('./apex create dashboard-item my-shop invoices');

        // Return
        return $help;
    }

}


