<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\Cli\CLi;
use Apex\Db\Interfaces\DbInterface;
use redis;

/**
 * Database installer
 */
class DatabaseInstaller
{

    // Properties
    public static string $db_class = \Apex\Db\Drivers\mySQL\mySQL::class;
    public static ?array $connection_info = null;
    public static array $default_ports = [
        'mySQL' => 3306,
        'PostgreSQL' => 5432,
        'SQLite' => 0
    ];

    /**
     * Setup
     */
    public static function setup(Cli $cli, redis $redis):void
    {

        // Check if slave server
        $opt = $cli->getArgs();
        $is_slave = $opt['slave'] ?? false;
        if ($is_slave === true) { 
            return;
        }

        // Get connection info
        $info = self::getConnectionInfo($cli);

        // Test connection
        self::testConnection($cli, $info);

        // Add to redis
        $redis->hmset('config:db.master', $info);
    }

    /**
     * Get connection info
     */
    private static function getConnectionInfo(Cli $cli):array
    {

        // Check if already defined
        if (self::$connection_info !== null) { 
            return self::$connection_info;
        }

        // Send header
        $cli->sendHeader('SQL Database Information');

        // Load bootstrap file
        $cntr_items = require(SITE_PATH . '/boot/container.php');
        self::$db_class = $cntr_items[DBInterface::class];

        // Get driver
        $parts = explode("\\", self::$db_class);
        $db_driver = array_pop($parts);

        // Initialize
        $info = [
            'dbname' => '', 
            'user' => '',
            'password' => '',
            'host' => 'localhost', 
            'port' => self::$default_ports[$db_driver]
        ];

        // Check cli options
        $opt = $cli->getArgs(['dbname', 'dbuser', 'dbpassword', 'dbhost', 'dbport']);
        foreach (['dbname', 'user', 'password', 'host', 'port'] as $var) { 
            $var_name = $var == 'dbname' ? 'dbname' : 'db' . $var;
            if (!isset($opt[$var_name])) { 
                continue;
            }
            $info[$var] = $opt[$var_name];
        }

        // Get user input
        if ($db_driver == 'SQLite' && !isset($opt['dbname'])) { 
            $dbname = $cli->getInput('SQLite Filepath [./storage/apex.db]: ', './storage/apex.db');
            if (!str_starts_with($dbname, '/')) {
                $dbname = SITE_PATH . '/' . ltrim($dbname, './');
            }
            $info['dbname'] = $dbname;

        } elseif (!isset($opt['dbname'])) {
            $info['dbname'] = $cli->getInput('Database Name: ');
            $info['user'] = $cli->getInput('Database Username: ');
            $info['password'] = $cli->getInput('Database Password: ');
            $info['host'] = $cli->getInput('Database Host [localhost]: ', 'localhost');
            $info['port'] = (int) $cli->getInput('Database Port [' . self::$default_ports[$db_driver] . ']: ', (string) self::$default_ports[$db_driver]);
        }

        // Return
        return $info;
    }

    /**
     * Test connection
     */
    private static function testConnection(Cli $cli, array $info):void
    {

        // Check for confirm
        $opt = $cli->getArgs();
        $confirm = $opt['confirm'] ?? false;

        // Load db driver
        $db = new self::$db_class($info);

        // Test connection
        if (!$db->connect($info['dbname'], $info['user'], $info['password'], $info['host'], $info['port'])) { 
            $cli->error('Unable to connect to database with supplied information.  Please run installation again.');
            exit(0);
        }

        // Check for tables
        $tables = $db->getTableNames();
        if (count($tables) == 0) { 
            return;
        }

        // Prompt to delete tables
        if ($confirm === false && !$cli->getConfirm('Existing database tables detected.  This operation will delete all existing tables within the database.  Are you sure you want to continue? (y/n) [n]: ', 'n')) {  
            $cli->send('Ok, installation aborted.  If this is a slave server / installation, please use the --slave option and run installation again.\r\n\r\n');
            exit(0);
        }

        // Delete tables
        $db->dropAllTables();
    }

}


