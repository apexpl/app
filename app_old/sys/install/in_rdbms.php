<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use apex\libc\redis;
use apex\app\cli\cli;
use apex\app\interfaces\DBInterface;


/**
 * Handles installation of the mySQL database, or 
 * other RDBMS being used such as PostgreSQL.
 */
class in_rdbms
{

    // Default ports
    private static array $default_ports = [
        'mysql' => 3306, 
        'postgresql' => 5432, 
        'default' => 3306
    ];

    // Default hosts
    private static array $default_hosts = [
        'mysql' => 'localhost', 
        'postgresql' => 'localhost', 
        'default' => 'localhost'
    ];

    // Properties
    private static string $db_class = \apex\app\db\mysql::class;

/**
 * Setup SQL database
 */
public static function setup():void
{

    // Check if slave server
    $is_slave = cli::$options['slave'] ?? false;
    if (is_slave === true) { 
        return;
    }

    // Get info
    $info = self::get_info();

    // Test connection
    self::test_connect($info);

}

/**
 * Get connection info
 */
private static function get_info():array
{

    // Send header
    cli::send_header('SQL Database Information\n');

    // Load bootstrap file
    $di_items = require(SITE_PATH . '/bootstrap/http.php');
    self::$db_class = $db_items[DBInterface::class][0];

    // Get driver
    $parts = explode("\\", self::$db_class);
    $db_driver = array_pop($parts);
    app::set_db_driver($db_driver);

    // Check cli options
    if (isset(cli::$options['dbname']) && isset(cli::$options['dbuser'])) { 
        $dbname = cli::$options['dbname'];
        $user = cli::$options['dbuser'];
        $password = cli::$options['dbpassword'] ?? '';
        $host = cli::$options['dbhost'] ?? self::$default_hosts[$db_driver];
        $port = cli::$options['dbport'] ?? self::$default_ports[$db_driver];

    // Get user input
    } else { 
        $dbname = cli::get_input('Database Name: ');
        $user = cli::get_input('Database Username: ');
        $password = cli::get_input('Database Password: ');
        $host = cli::get_input('Database Host [' . self::$default_hosts[$db_driver] . ']: ', self::$default_hosts[$db_driver]);
        $port = cli::get_input('Database Port [' . self::$default_ports[$db_driver] . ']: ', self::$default_ports[$db_driver]);

    }

    // Save to redis
    $info = [
        'dbname' => $dbname, 
        'dbuser' => $user, 
        'dbpass' => $password, 
        'dbhost' => $host, 
        'dbport' => (int) $port, 
        'dbuser_readonly' => '', 
        'dbpass_readonly' => ''
    ];

    // Return
    return $info;

}

/**
 * Test connection
 */
private static function test_connect(array $info):void
{

    // Load db driver
    $db = new self::$db_class();

    // Test connection
    if (!$db->connect($info['dbname'], $info['dbuser'], $info['dbpass'], $info['dbhost'], (int) $info['dbport'])) { 
        parent::install_error('Unable to connect to database with supplied information.  Please run installation again.');
    }

    // Add to redis
    redis::hmset('config:db_master', $info);

    // Check for tables
    $tables = $db->show_tables();
    if (count($tables) == 0) { 
        return;
    }

    // Prompt to delete tables
    $ok = cli::get_input('Existing database tables detected.  This operation will delete all existing tables within the database.  Are you sure you want to continue? (y/n) [n]: ', 'n');
    if (strtolower($ok) != 'y') { 
        parent::install_error('Ok, installation aborted.  If this is a slave server / installation, please use the --slave option and run installation again.');
    }

    // Delete tables
    foreach ($tables as $table_name) { 
        $db->query("DROP TABLE $table_name");
    }

}

}



