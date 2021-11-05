<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Symfony\Component\Process\Process;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Execute SQL
 */
class Sql Implements CliCommandInterface
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

        // Get options
        $opt = $cli->getArgs(['file']);
        $file = $opt['file'] ?? '';

        // Check
        if ($file == '' && !isset($args[0])) { 
            $this->connect($cli);
            return;
        } elseif ($file != '' && !file_exists($file)) { 
            $cli->error("No file exists at the location, $file");
            return;
        } elseif (!$dbinfo = $this->redis->hgetall('config:project')) {
            $cli->error("There is no active project on this system.");
            return;
        } elseif ($dbinfo['is_staging'] != 1) {
            $cli->error("The active project does not have a corresponding staging environment setup on it.");
            return;
        }

        // Connect to the database
        $dbclass = $this->db::class;
        $db = new $dbclass($dbinfo);

        // Execute file, if needed
        if ($file != '') { 
            $db->executeSqlFile($file);
            $cli->send("Successfully executed all SQL statements within the file, $file\r\n\r\n");
            return;
        }

        // Execute
        $sql = $args[0];
        $result = $db->query($sql);

        // Give results of select statement
        $res = [];
        if (preg_match("/^(select|describe|show)/i", $sql)) { 

            // Get column names
            $column_names = [];
            for ($x=0; $x < $result->columnCount(); $x++) { 
                $info = $result->getColumnMeta($x);
                $column_names[] = $info['name'];
            }
            $res[] = $column_names;

            // Go through rows
            while ($row = $db->fetchArray($result)) { 
                $res[] = $row;
            }

            // Display table
            $cli->sendTable($res);
        } else { 
            $cli->send("Successfully executed SQL statement against database.\r\n\r\n");
        }

    }

    /**
     * Connect
     */
    public function connect(Cli $cli):void
    {

        // Get db driver
        $parts = explode("\\", $this->db::class);
        $driver = array_pop($parts);

        // Get database command
        $cmd = match($driver) {
            'PostgreSQL' => 'psql',
            'SQLite' => 'sqlite3',
            default => 'mysql'
        };

        // Get dbinfo
        if (!$dbinfo = $this->redis->hgetall('config:project')) {
            $cli->error("There is currently no active project checked out on this system.");
            return;
        }

        // Set args
        if ($driver == 'SQLite') {
            $args = [$cmd, $dbinfo['dbname']];
        } else {
            $args = [$cmd, '-u' . $dbinfo['user'], '-p' . $dbinfo['password'], '-h' . $dbinfo['host'], '-P' . $dbinfo['port'], $dbinfo['dbname']];
        }

        // Run process
        $process = new Process($args);
        $process->setTty(true);
        $process->run();
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Execute SQL Statement',
            usage: 'sql ]<SQL>] [--file=<FILENAME>]',
            description: 'Execute a single SQL statement against the database, or a SQL file.  If no arguments are passed to the command, you will be connected directly to the SQL database and its prompt.'
        );

        $help->addParam('sql', 'The SQL statement to execute against the database.');
        $help->addFlag('--file', 'Optional location of the file containing SQL statements to execute.');
        $help->addExample('./apex sql "SELECT * FROM admin"');
        $help->addExample('./apex sys sql --file dev.sql');

        // Return
        return $help;
    }

}


