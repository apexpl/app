<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\components;
use apex\app\cli\core;
use apex\app\exceptions\{ApexException, RepoException};


/**
 * Handles all CLI functionality within Apex.
 */
class Cli
{

    // Properties
    public static array $args = [];
    public static array $options = [];

/**
 * Run a CLI command from apex phar archive.
 */
public static function run()
{

    // Get arguments
    list($args, $options) = self::get_args();
    $command = array_shift($args);

    // Parse method
    if (preg_match("/^(.+?)\.(.+)$/", $command, $match)) { 
        $module = $match[1];
        $method = $match[2];
    } else { 
        $module = 'core';
        $method = $command;
    }

    // Check for core module
    $class_name = "apex\\app\\cli\\c_" . $module;
    if (app::has($class_name)) { 

        // Check if method exists
        $client = app::make($class_name);
        if (!method_exists($client, $method)) { 
            return tr("No CLI command exists at '{1}'.  Use 'help' to list all available commands.", $command) . "\n";
        }

        // Execute command
        return app::call([$class_name, $method], ['vars' => $args]);
    }

    // Check if component exists
    if (!list($package, $parent, $alias) = components::check('cli', $module . ':' . $method)) { 
        return tr("No CLI command exists at '{1}'.  Use 'help' to list all available commands.", $command) . "\n";
    }

    // Call process
    $class_name = "apex\\" . $module . "\\cli\\" . $method;
    return $app->call([$class_name, 'process'], ['args' => $vars]);

}

/**
 * Get command line arguments and options
 */
public static function get_args(array $has_value = []):array
{

    // Initialize
    global $argv;
    list($args, $options, $tmp_args) = [[], [], $argv];
    array_shift($tmp_args);

    // Go through args
    while (count($tmp_args) > 0) { 
        $var = array_shift($tmp_args);

        // Long option with =
        if (preg_match("/^--(\w+?)=(.+)$/", $var, $match)) { 
            $options[$match[1]] = $match[2];

        } elseif (preg_match("/^--(.+)$/", $var, $match) && in_array($match[1], $has_value)) { 


            $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
            if ($value == '=') { 
                $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
            }
            $options[$match[1]] = $value;

        } elseif (preg_match("/^--(.+)/", $var, $match)) { 
            $options[$match[1]] = true;

        } elseif (preg_match("/^-(\w+)/", $var, $match)) { 
            $chars = str_split($match[1]);
            foreach ($chars as $char) { 
                $options[$char] = true;
            }

        } else { 
            $args[] = $var;
        }
    }

    // Set properties
    self::$args = $args;
    self::$options = $options;

    // Return
    return array($args, $options);

}
/**
 * Get input from the user.
 */
public static function get_input(string $label, string $default_value = ''):string
{ 

    // Echo label
    self::send($label);

    // Get input
    $value = trim(fgets(STDIN));
    if ($value == '') { $value = $default_value; }

    // Return
    return $value;

}

/**
 * Send output to user.
 */
public static function send(string $data):void
{
    echo $data;
}

/**
 * Send header to user
 */
public static function send_header(string $label):void
{
    self::send("------------------------------\n");
    self::send("-- $label\n");
    self::send("------------------------------\n\n");

}

/**
 * Ask user to choose a repository via CLI.
 */
public static function get_repo()
{ 

    // Check number of repos
    $count = db::get_field("SELECT count(*) FROM internal_repos WHERE is_active = 1");
    if ($count == 0) { 
        throw new RepoException('no_repos_exist');

    // If only one repo
    } elseif ($count == 1) { 
        $repo_id = db::get_field("SELECT id FROM internal_repos");
        return (int) $repo_id;
    }

    // List repos
    self::send("Available Repositories:\n\n");
    $rows = db::query("SELECT * FROM internal_repos WHERE is_active = 1 ORDER BY id");
    foreach ($rows as $row) { 
        self::send("      [" . $row['id'] . "] $row[name] ($row[host])\n");
    }

    // Get repository
        $repo_id = self::get_input("\nWhich repository to use? ");

    // Ensure repo exists
    if (!$row = db::get_idrow('internal_repos', $repo_id)) { 
        throw new RepoException('not_exists', $repo_id);
    }

    // Return
    return (int) $repo_id;

}

}


