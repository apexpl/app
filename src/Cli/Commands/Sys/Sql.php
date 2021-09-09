<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Execute SQL
 */
class Sql Implements CliCommandInterface
{

    #[Inject(Db::class)]
    private Db $db;

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
            $cli->error("You did not specify an SQL statement to execute.");
            return;
        } elseif ($file != '' && !file_exists($file)) { 
            $cli->error("No file exists at the location, $file");
            return;
        }

        // Execute file, if needed
        if ($file != '') { 
            $this->db->executeSqlFile($file);
            $cli->send("Successfully executed all SQL statements within the file, $file\r\n\r\n");
            return;
        }

        // Execute
        $sql = $args[0];
        $result = $this->db->query($sql);

        // Give results of select statement
        $res = [];
        if (preg_match("/^select/i", $sql)) { 

            // Get column names
            $column_names = [];
            for ($x=0; $x < $result->columnCount(); $x++) { 
                $info = $result->getColumnMeta($x);
                $column_names[] = $info['name'];
            }
            $res[] = $column_names;

            // Go through rows
            while ($row = $this->db->fetchArray($result)) { 
                $res[] = $row;
            }

            // Display table
            $cli->sendTable($res);
        } else { 
            $cli->send("Successfully executed SQL statement against database.\r\n\r\n");
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Execute SQL Statement',
            usage: 'sql ]<SQL>] [--file=<FILENAME>]',
            description: 'Execute a single SQL statement against the database, or a SQL file.'
        );

        $help->addParam('sql', 'The SQL statement to execute against the database.');
        $help->addFlag('--file', 'Optional location of the file containing SQL statements to execute.');
        $help->addExample('./apex sql "SELECT * FROM admin"');
        $help->addExample('./apex sys sql --file dev.sql');

        // Return
        return $help;
    }

}


