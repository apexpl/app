<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Adapters\MigrationsConfig;
use Apex\Migrations\Handlers\Remover;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Rollback
 */
class Rollback implements CliCommandInterface
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(MigrationsConfig::class)]
    private MigrationsConfig $config;

    #[Inject(Remover::class)]
    private Remover $remover;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get options
        if (!list($txid, $package, $last, $all) = $this->getOptions($cli, $args)) { 
            return;
        }

        // Transaction
        if ($txid > 0) { 
            $this->remover->rollbackTransaction((int) $txid);
        } elseif ($package != '' && $all === true) { 
            $this->remover->removePackage($package);
        } elseif ($package != '' && $last > 0) { 
            $this->remover->rollbackLastPackage($package, $last);
        } elseif ($last > 0) { 
            $this->remover->rollbackLastTransaction($last);
        } else { 
            $cli->send("\r\nInvalid options.  Please run 'apex help migration rollback' for details on this command.  Nothing to do.\r\n\r\n");
            return;
        }

        // Send response
        $cli->send("\r\nSuccessfully rollback database as specified.  Database up to date.\r\n\r\n");
    }

    /**
     * Get options
     */
    private function getOptions(Cli $cli, array $args):array
    {

        // Get options
        $opt = $cli->getArgs(['package','txid','last','all']);
        $package = $opt['package'] ?? '';
        $txid = (int) ($opt['txid'] ?? 0);
        $last = (int) ($opt['last'] ?? 0);
        $all = isset($opt['all']) ? true : false;

        // Check package, if needed
        if ($package != '' && !$pkg = $this->config->getPackage($package)) { 
            $cli->send("\r\nThe package '$package' either does not exist or no migrations have been installed against it.  Nothing to do.\r\n\r\rn");
            return null;
        }

        // Check transaction
        $table_name = $this->config->getTableName();
        if ($txid != '' && !$row = $this->db->query("SELECT * FROM $table_name WHERE transaction_id = %i", $txid)) { 
            $cli->send("\r\nThe transaction id $txid does not exist.  Nothing to do.\rn\r\n");
            return null;
        }

        // Return
        return [$txid, $package, $last, $all];
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Rollback Migrations',
            usage: 'migration rollback [--package=] [--txid=] [--last=]',
            description: "Rollback previously installed database migrations."
        );

        // Add flags
        $help->addFlag('--package', 'Optional package alias to rollback migrations on.');
        $help->addFlag('--txid', 'Optional transaction id rollback to and including.');
        $help->addFlag('--last', 'The last number of migrations installed on a given package, or last number of transactions to rollback.');
        $help->addExample('./apex migration rollback --package myshop --last 3');

        // Return
        return $help;
    }

}






