<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys\Db;

use Apex\App\Cli\Cli;
use Apex\App\Attr\Inject;

/**
 * Abstract database command
 */
class AbstractDbCommand
{

    /**
     * Get database info
     */
    protected function getDatabaseInfo(Cli $cli, array $info):array
    {

        // Set vars
        $vars = [
            'dbname' => 'Database Name', 
            'user' => 'Username', 
            'password' => 'Password', 
            'host' => 'Host', 
            'port' => 'Port'
        ];

        // Get info as needed
        foreach ($vars as $var => $name) { 

            // Skip, if we have it
            if (isset($info[$var]) && $info[$var] != '') { 
                $cli->send($name . ': ' . $info[$var] . "\r\n");
                continue;
            }

            // Get default
            $default = $var == 'host' ? 'localhost' : '';
            if ($var == 'port') {
                $default = '3306';
            }

            // Get info
            $info[$var] = $cli->getInput($vars[$var] . "[$default]: ", $default);
        }

        // Return
        return $info;
    }


}


