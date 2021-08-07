<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\cli\cli;
use apex\app\exceptions\ApexException;

/**
 * Handles all remote access client functionality for the Apex CLI client.
 */
class c_remote
{

/**
 * Update remote API key info.
 *
 * @param array $vars The arguments passed to the command.
 */
public function update_remote_apikey($vars)
{

    // Set variables
    $host = $vars[0] ?? '';
    $key = $vars[1] ?? '';

    // Get info, if needed
    if ($host == '') { 
        echo "Install URL (ie. https://domain.com): "; $host = trim(readline());
    }
    if ($key == '') { 
        echo "API Key: "; $key = trim(readline());
    }
    $key = trim($key, '/');

    // Update config
    app::update_config_var('core:remote_api_host', $host);
    app::update_config_var('core:remote_api_key', $key);

    // Set response
    $response = "Successfully updated remote API access settings.  You may now copy over individual files as necessary.\n\n";
    $response .= "NOTE: Depending on server permissions, remote updates may take a couple minutes as they will need to be applied via crontab.\n\n";

    // Return
    return $response;

}

/**
 * Remote update - copy files
 *
 * @param array $vars The arguments passed to the CLI command.
 */
public function remote_copy($vars)
{

    // Send request to copy
    $client = app::make(remote_access_client::class);
    $response = $client->copy($vars);

    // Set variables
    $status = $response['status'];
    $files = $response['files'] ?? [];

    // Get response
    if ($status == 'ok') { 
        $response = "Successfully copied the following files, and modifications are now in place:\n\n";
    } elseif ($status == 'pending') { 
        $response = "The following files have been queued for updates, but are currently pending due to server permissions.  The files will be updated within the next couple minutes via crontab.\n\n";
    } else { 
        $response = "An unknown status was received from the server, with the response being: " . print_r($response) . "\n for the following files:\n\n";
    }
    foreach ($files as $file) { 
        $response .= "    $file\n";
    }

    // Return
    return "$response\n";

}

/**
 * Remote delete files
 *
 * @param array $vars The arguments passed to the command.
 */
public function remote_rm($vars)
{

    // Send request
    $client = app::make(remote_access_client::class);
    $response = $client->rm($vars);

    // Set variables
    $status = $response['status'];
    $files = $response['files'] ?? [];

    // Set response
    if ($status == 'ok') { 
        $response = "Successfully deleted from following files remotely:\n\n";
    } elseif ($status == 'pending') { 
        $response = "An unknown error occurred, and was unable to delete the following files remotely:\n\n";
    }
    foreach ($files as $file) { $response .= "    $file\n"; }

    // Return
    return $response;

}

/**
 * Remote save a component
 *
 * @param array $vars The arguments passed to the CLI command.
 */
public function remote_save($vars)
{

    // Set variables
    $type = $vars[0] ?? '';
    $comp_alias = $vars[1] ?? '';

    // Send request
    $client = app::make(remote_access_client::class);
    $response = $client->save($type, $comp_alias);

    // Set variables
    $status = $response['status'];
    $files = $response['files'] ?? [];

    // Set response
    if ($status == 'ok') { 
        $response = "The component was successfully remotely saved with type $response[type], alias $response[comp_alias], and owner $response[owner].  The following files were remotely saved:\n\n";
    } elseif ($status == 'pending') { 
        $response = "The component was successfully remotely saved, but due to server permissions will not be reflected on the server for a couple minutes until crontab runs, with type $response[type], alias $response[comp_alias], and owner $response[owner].  The following files were remotely saved:\n\n";
    } else { 
        $response = "An unknown error occured while trying to remotely save the component of type $response[type] with alias [response[comp_alias] with the following files:\n\n";
    }
    foreach ($files as $file) { $response .= "    $file\n"; }

    // Return
    return $response;

}

/**
 * Remotely delete a component
 *
 * @param array $vars The arguments passed to the CLI command.
 */
public function remote_delete($vars)
{

    // Set variables
    $type = $vars[0] ?? '';
    $comp_alias = $vars[1] ?? '';

    // Send request
    $client = app::make(remote_access_client::class);
    $response = $client->delete($type, $comp_alias);
    $status = $response['status'];

    // Set response
    if ($status == 'ok') { 
        $response = "The component was successfully deleted remotely with the type $response[type] and alias [$response[comp_alias].\n";
    } elseif ($status == 'pending') { 
        $response = "The component was successfully deleted remotely, but due to server permissions will take a couple minutes to take effect until crontab runs, with the type $response[type] and alias [$response[comp_alias].\n";
    } else { 
        $response = "An uknown error occurred while trying to delete the component remotely with type $response[type] and alias $response[comp_alias]\n\n";
    }

    // Return
    return $response;

}

/**
 * Execute SQL on a remote server.
 *
 * @param array $vars The  arguments passed to the CLI command.
 *
 * @return string The response of the command.
 */
public function remote_sql($vars)
{

    // Initialize
    $sql = rtrim(implode(' ', $vars), ";");

    // Send request
    $client = app::make(remote_access_client::class);
    $res = $client->sql($sql);

    // Set response
    if ($res['status'] == 'ok') { 
        $response = "The SQL was executed successfully with the following results:\n\n";
        $response .= json_encode($res['rows']);
    } else { 
        $response = "The following error occured when executing the SQL statement:\n\n" . $res['message'];
    }

    // Return
    return $response;

}

/**
 * Remotely scan a package
 *
 * @param array $vars The arguments passed to the CLI command.
 *
 * @return string The response message.
 */
public function remote_scan($vars)
{

    // Check
    if (!isset($vars[0])) { 
        throw new ApexException('error', "You did not specify a package to scan.");
    }

    // Send request
    $client = app::make(remote_access_client::class);
    $response = $client->scan($vars[0]);

    // Return
    return "Successfully scanned the remote package, $vars[0]\n";

}

