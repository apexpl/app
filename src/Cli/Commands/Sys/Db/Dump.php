<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * DUmp database
 */
class Dump implements CliCommandInterface
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(redis::class)]
    private redis $redis;

    
    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $file = $args[0] ?? 'dump.sql';

        // Check if file exists
        if (file_exists(SITE_PATH . '/' . $file)) {
            $cli->send("The SQL dump file at $file already exists on the local machine.  This operation will overwrite the file.");
            if (!$cli->getConfirm("Are you sure you want to continue?")) {
                $cli->send("Ok, goodbye.\r\n\r\n");
                return;
        }
            unlink(SITE_PATH . '/' . $file);
    }

        // Get db driver
        $parts = explode("\\", $this->db::class);
        $db_driver = array_pop($parts);

        // Check for SQLite
        if ($db_driver == 'SQLite') {
            $cli->error("You are running SQLite for the database, hence there is no dump available.");
            return;
        } elseif (!$dbinfo = $this->redis->hgetall('config:db.master')) {
            $cli->error("There is no database connection information within redis.");
            return;
        }

        // Get cmd
        $cmd = "mysqldump -u$dbinfo[user] -p$dbinfo[password] -h$dbinfo[host] -P$dbinfo[port] $dbinfo[dbname] > " . SITE_PATH . '/' . $file;
        shell_exec($cmd);

        // Check for success
        if (!file_exists(SITE_PATH . '/' . $file)) {
            $cli->error("There was an error in dumping the database.  Please ensure the database information is correct, or manually dump it.");
            return;
        }

        // Success
        $cli->success("The $db_driver database has been successfully dumped, and the SQL dump file can be found at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Dump SQL Database',
            usage: 'sys db dump [<FILE>]',
            description: 'Dumps the SQL database into a SQL dump file.'
        );

        $help->addParam('file', 'Optional filename to dump the SQL database to.  Defaults to dump.sql');
        $help->addExample('./apex sys db dump mydb.sql');

        // Return
        return $help;
    }

}


