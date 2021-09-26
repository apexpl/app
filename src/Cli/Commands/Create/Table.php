<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\{CliCommandInterface, DataTableInterface};
use redis;

/**
 * Create table
 */
class Table implements CliCommandInterface
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

        // Get dbtable
        $opt = $cli->getArgs(['dbtable']);
        $dbtable = $opt['dbtable'] ?? '';
        if ($dbtable == '') {
            $dbtable = $pkg->getAlias() . '_' . $this->convert->case($alias, 'lower');
        }

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/DataTables/$alias.php")) { 
            $cli->error("The data table already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('data_table', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias,
            'dbtable' => $dbtable,
            'columns' => "        'id' => 'ID'",
            'format_code' => ''
        ]);

        // Add to redis
        $class_name = $this->opus_helper->pathToNamespace($files[0]);
        $this->redis->sadd('config:interfaces:' . DataTableInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new data table which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Data Table',
            usage: 'create table <PKG_ALIAS> <ALIAS> [--dbtable <TABLE>]',
            description: 'Create new data table component.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create data table within.');
        $help->addParam('alias', 'The alias / filename of the data table to create.');
        $help->addFlag('--dbtable', 'Optional database table name that will be used for the data table.');
        $help->addExample('./apex create table my-shop invoices');

        // Return
        return $help;
    }

}


