<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, debug};
use apex\app\cli\cli;
use apex\app\codegen\{crud, model};
use apex\app\exceptions\ApexException;

/**
 * Class that handles all scaffolding and code generation functionality 
 * of the Apex CLI client.
 */
class c_gen
{


/**
 * Generate CRUD
 */
public function crud(array $vars):string
{

    // Get command line options
    list($args, $options) = cli::get_args(['file']);

    // Check for crud.yml file
    $file = cli::$options['file'] ?? 'crud.yml';
    if (!file_exists(SITE_PATH . '/' . $file)) { 
        throw new ApexException('error', "No file exists within the installation directory at, $file");
    }

    // Create CRUD scaffolding
    $client = make(crud::class);
    list($alias, $package, $files) = $client->create($file);

    // Set response
    $response = "Successfully created new CRUD components with alias '$alias' under the package '$package'.  The following files have been created, and may be modified as necessary:\n\n";
    foreach ($files as $file) { 
        $response .= "      $file\n";
    }

    // Return
    return $response;

}

/**
 * Generate a model
 */
public function model(array $vars):string
{

    // Perform checks
    if (!isset($vars[0])) { 
        throw new ApexException('error', "You did not specify a comp_alias as the first argument");
    } elseif (!isset($vars[1])) { 
    throw new ApexException('error', "You did not specify a database table name as the second argument.");
    } elseif (!preg_match("/^(.+?):(.+)/", $vars[0], $match)) { 
        throw new ApexException('error', "The comp_alias first argument needs to be formatted in PACKAGE:ALIAS format");
    }

    // Create CRUD scaffolding
    $client = make(model::class);
    list($alias, $package, $files) = $client->create($match[1], $match[2], $vars[1]);

    // Set response
    $response = "Successfully created new model libraries with alias '$alias' under the package '$package'.  The following files have been created, and may be modified as necessary:\n\n";
    foreach ($files as $file) { 
        $response .= "     $file\n";
    }

    // Return
    return $response;

}

}

