<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, debug, redis};
use apex\app\cli\cli;
use apex\app\pkg\package;
use apex\app\sys\network\repo;
use apex\app\exceptions\{ApexException, RepoException};

/**
 * Handles repository functionality for the Apex CLI client.
 */
class c_repo
{


/**
 * Add repository
 */
public function add(array $vars, repo $client):string
{ 

    // Get options
    list($args, $options) = cli::get_args(['host', 'user', 'password']);

    // Check options
    if (isset($options['host'])) { 
        $host = $options['host'];
        $user = $options['user'] ?? '';
        $pass = $options['password'] ?? '';

    // Get user input
    } else { 
        $host = cli::get_input('host / Domain Name: ');
        $user = cli::get_input('Username: ');
        $pass = cli::get_input('Password: ');
    }

    // Initial checks
    if ($host == '') { 
        throw new RepoException('not_exists', 0, $host);
    }

    // Add repo
    $client->add($host, $user, $pass);
    debug::add(4, tr("CLI: Added new repository, {1}", $host), 'info');

    // Return
    return "Successfully added new repository, $host\n";

}

/**
 * Update repo with new username / password.
 */
public function update($vars, repo $client)
{ 

    // Get options
    list($args, $options) = cli::get_args(['user', 'password']);

    // Check repo
    $host = $vars[0] ?? '';
    if (!$row = db::get_row("SELECT * FROM internal_repos WHERE host = %s", $host)) { 
        throw new RepoException('host_not_exists', 0, $host);
    }

    // Check options

    // Check options
    if (isset($options['user']) && isset($options['password'])) { 
        $user = $options['user'];
        $pass = $options['password'];

    // Get user input
    } else {
        $user = cli::get_input('Username: ');
        $pass = cli::get_input('Password: ');
    }

    // Update repo
    $client->update((int) $row['id'], $user, $pass);
    debug::add(4, tr("CLI: Updated repository login information, host: {1}", $vars[0]), 'info');

    // Give response
    return "Successfully updated repo with new username and password.\n";

}

/**
 * Delete repo
 */
public function delete(array $vars, repo $client):string
{

    // Check repo
    $host = $vars[0] ?? '';
    if (!$repo = db::get_row("SELECT * FROM internal_repos WHERE host = %s", $host)) { 
        throw new RepoException('host_not_exists', 0, $host);
    }

    // Check for packages assigned
    $count = db::get_field("SELECT count(*) FROM internal_packages WHERE repo_id = %i", $repo['id']);
    if ($count > 0) { 
        cli::send("One or more packages are currently assigned to this repository.  By deleting this repository, the following packages will also be deleted:\n\n");

        // Go through packages
        $rows = db::query("SELECT * FROM internal_packages WHERE repo_id = %i ORDER BY alias");
        foreach ($rows as $row) { 
        $name = $row['alias'] . ' - ' . $row['name'] . ' v' . $row['version'];
            cli::send("      $name\n");
        }

        // Confirm deletion
        $ok = cli::get_input("Are you sure you want to continue and delete the above packages? (y/n) [n]: ", 'n');
        if (strtolower($ok) != 'y') { 
            return "Ok, goodbye.\n";
        }

        // Delete all packages
        foreach ($rows as $row) { 
            $package = make(package::class);
            $package->remove($row['alias']);
        }
    }

    // Delete repo
    db::query("DELETE FROM internal_repos WHERE id = %i", $repo['id']);

    // Return
    return "Successfully deleted the repository, $repo[host]\n";

}

}



