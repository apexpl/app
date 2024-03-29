<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Pkg\Gpt\GptTable;
use Apex\App\Interfaces\Opus\{CliCommandInterface, DataTableInterface};
use Apex\App\Attr\Inject;
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

    #[Inject(GptTable::class)]
    private GptTable $gpt_table;

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
            $cli->error("You did not specify a --dbtable flag, which is required for AI assisted generation.");
            return;
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

        // AI assistance
        $yaml = $pkg->getConfig();
        $hashes = $yaml['hashes'] ?? [];
        $this->gpt_table->initial($pkg_alias, $class_name, $dbtable, array_keys($hashes));

        // Success message
        $cli->success("Successfully created new data table which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate Data Table',
            usage: 'gpt table <PKG_ALIAS> <ALIAS> --dbtable <TABLE>',
            description: 'Generate new data table component with AI assistance.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create data table within.');
        $help->addParam('alias', 'The alias / filename of the data table to create.');
        $help->addFlag('--dbtable', 'Name of database table to generate table component for.');
        $help->addExample('./apex gpt table my-shop invoices --dbtable shop_invoices');

        // Return
        return $help;
    }

}


