<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\Svc\Convert;
use Apex\App\CLi\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\Migrations\Migrations;
use Apex\Migrations\Handlers\HistoryLoader;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * History
 */
class History implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(HistoryLoader::class)]
    private HistoryLoader $loader;

    #[Inject(Migrations::class)]
    private Migrations $migrations;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['package', 'txid', 'start', 'limit', 'sort']);
        $pkg_alias = $opt['package'] ?? '';
        $txid = $opt['txid'] ?? '';
        $start = $opt['start'] ?? 0;
        $limit = $opt['limit'] ?? 0;
        $sort_desc = isset($opt['sort']) && strtolower($opt['sort']) == 'asc' ? false : true;

        // View transaction
        if ($txid != '') { 
            $this->viewTransaction($cli, (int) $txid);
            return;

        // View package
        } elseif ($pkg_alias != '') { 
            $this->viewPackage($cli, $pkg_alias, (int) $limit, (int) $start, $sort_desc);
            return;
        }


        // List transactions
        $txs = $this->loader->listTransactions((int) $limit, (int) $start, $sort_desc);
        if (count($txs) == 0) { 
            $cli->send("\r\nThere is no transaction history.  Nothing to show.\r\n");
            return;
        }
        $cli->sendHeader("Transactions");

        // Go through transactions
        foreach ($txs as $txid => $row) { 
            $date = date('D, d M Y H:i:s', $row['installed_at']->getTimestamp());
            $cli->send("TxID $txid -- Installed $row[total] migrations in " . $this->migrations->formatSecs($row['ms']) . " [$date]\r\n");
        }

        // Send footer
        $cli->send("\r\nYou may view details on any transaction with:\r\n      apex-migrations history --txid XX\r\n\r\n");
        $cli->send("You may rollback your database up to and including any transaction with:\r\n      apex-migrations rollback --txid XX\r\n\r\n");
    }

    /**
     * View transaction
     */
    private function viewTransaction(Cli $cli, int $txid):void
    {

        // Get transaction
        if (!$installs = $this->loader->getTransaction($txid)) { 
            $cli->send("\r\nThe txid $txid does not exist.  Nothing to show.\r\n\r\n");
            return;
        }
        $cli->sendHeader("Transaction ID: $txid");

        // Go through installed
        list($total, $total_ms) = [0, 0];
        foreach ($installs as $row) {
            $cli->send("Package: $row[package] -- installed $row[class_name] in " . $this->migrations->formatSecs($row['ms']) . "\r\n");
            $total++;
            $total_ms += $row['ms'];
        }

        // SEnd footer
        $cli->send("\r\nTotal Installs: $total in " . $this->migrations->formatSecs($total_ms) . "\r\n\r\n");
    }

    /**
     * View package
     */
    private function viewPackage(Cli $cli, string $package, int $limit = 0, int $start = 0, bool $sort_desc = true):void
    {

        // Get installed
        if (!$installed = $this->loader->getPackage($package, $limit, $start, $sort_desc)) { 
            $cli->send("\r\nThe package '$package' does not exist or has never had any migrations installed on it.  Nothing to show.\r\n\r\n");
            return;
        }
        $cli->sendHeader("Package: $package");

        // GO through installed
        list($total, $total_ms) = [0, 0];
        foreach ($installed as $row) { 
            $cli->send("TxID $row[txid] -- installed $row[class_name] in " . $this->migrations->formatSecs($row['ms']) . "\r\n");
            $total++;
            $total_ms += $row['ms'];
        }

        // Send footer
        $cli->send("\r\nTotal $total installed in " . $this->migrations->formatSecs($total_ms) . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'View Migration History',
            usage: 'migration status <PKG_ALIAS>',
            description: "View history of all previous migrations installed."
        );

        // Add flags
        $help->addFlag('--pkacage', 'Optional package alias to view history of.');
        $help->addFlag('--txid', 'Optional transaction id to view migrations of.');
        $help->addParam('--last', 'The last number of previous migrations to view.');
        $help->addParam('--sort', 'Optional sort parameter, can be either asc or desc, defaults to desc.');

        // Return
        return $help;
    }

}

