<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\Cli as CliUtils;
use Apex\App\Cli\CliHelpScreen;
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Create CLI command
 */
class Cli implements CliCommandInterface
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
    public function process(CliUtils $cli, array $args):void
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
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/Cli/$alias.php")) { 
            $cli->error("The CLI command already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('cli', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Add to redis
        $class_name = $this->opus_helper->pathToNamespace($files[0]);
        $this->redis->sadd('config:interfaces:' . CliCommandInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new CLI command which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(CliUtils $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create CLI Command',
            usage: 'create cli <PKG_ALIAS> <ALIAS>',
            description: 'Create new CLI command component.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create CLI command within.');
        $help->addParam('alias', 'The alias / filename of the CLI command to create.');
        $help->addExample('./apex create cli my-shop invoices');

        // Return
        return $help;
    }

}


