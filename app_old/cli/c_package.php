<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\app\cli\cli;
use apex\app\sys\network\network;
use apex\app\pkg\package;
use apex\app\exceptions\{ApexException, PackageException};


/**
 * Handles the package specific commands for the Apex CLI client.
 */
class c_package
{

/**
 * List all packages
 */
public function list(array $vars, network $client)
{

    // Get packages
    $packages = $client->list_packages();

    // Get response
    $response = '';
    foreach ($packages as $vars) { 
        $response .= $vars['alias'] . ' -- ' . $vars['name'] . ' (' . $vars['author_name'] . ')';
    }

    // Debugh   // Debug
    debug::add(4, 'CLI: package.list', 'info');

    // Return
    return $response;

}

/**
 * Create package
 */
public function create(array $vars, package $package, network $network)
{ 

// Initialize
    $pkg_alias = $vars[0] ?? '';
    $repo_id = $vars[1] ?? 0;
    debug::add(4, tr("CLI: Starting creation of package: {1}", $pkg_alias), 'info');

    // Validate package alias
    if ($row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('exists', $pkg_alias);
    } elseif (!$package->validate_alias($pkg_alias)) { 
        throw new PackageException('invalid_alias', $pkg_alias);
    }

    // Check if package exists in any repos
    $repos = $network->check_package($pkg_alias);
    if (count($repos) > 0) { 
        cli::send("The package '$pkg_alias' already exists in the following repositories:\n");
        foreach ($repos as $repo) { 
            cli::send("    $repo\n");
        }

        // Confirm package creation
        $ok = cli::get_input("\nAre you sure you want to create the package '$pkg_alias' (y/n) [n]?: ", 'n');
        if (strtolower($ok) != 'y') { 
            return "Ok, goodbye.";
        }
    }

    // Get repo ID#, if needed
    if ($repo_id == 0) { 
        $repo_id = cli::get_repo();
    }

    // Create package
    $package_id = $package->create((int) $repo_id, $pkg_alias, $name);
    debug::add(4, tr("CLI: Completed creation of package: {1}", $pkg_alias), 'info');

    // Return
    return "Successfully created the new package '$pkg_alias', and you may begin development.\n\n";

}

/**
 * Delete
 */
public function delete(array $vars):string
{ 

    // Ensure package exists
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $vars[0])) { 
        throw new PackageException('not_exists', $vars[0]);
    }
    debug::add(4, tr("CLI: Starting deletion of package: {1}", $vars[0]), 'info');

    // Delete package
    $client = make(package::class);
    $client->remove($vars[0]);
    debug::add(4, tr("CLI: Completed deletion of package: {1}", $vars[0]), 'info');

    // Return
    return "Successfully deleted the package, $vars[0]\n";

}

/**
 * Publish
 */
public function publish(array $vars)
{ 

    // Checks
    if (!isset($vars[0])) { 
        throw new PackageException('undefined');
    }

    // Publish
    $response = '';
    foreach ($vars as $alias) { 

        // Check package exists
        if (!$row = db::get_row("SELECT * FROm internal_packages WHERE alias = %s", $alias)) { 
            $response .= "Package does not exist in this system, $alias\n";
            continue;
        }
        debug::add(4, tr("CLI: Starting to publish package: {1}", $alias), 'info');

        // Publish
        $client = make(package::class);
        $client->publish($alias);

        // Add to response
        $response .= "Successfully published the package, $alias\n";
        debug::add(4, tr("CLI: Completed publishing package: {1}", $alias), 'info');
    }

    // Return
    return $response;

}

/**
 * Merge package with latest upgrades
 */
public function merge(array $vars):string
{

    // Get package
    $pkg_alias = $vars[0] ?? '';
    if (!$row = db::get_row("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'open'", $pkg_alias)) { 
        throw new ApexException('error', 'There is no upgrade open on this package, hence a merge is not possible.');
    } elseif (!$repo_id = db::get_field("SELECT repo_id FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Download the latest version of the package.
    $client = make(package::class);
    list($tmp_dir, $repo_id, $vars) = $client->download($pkg_alias, (int) $repo_id);

    // Perform merge
    list($new_files, $merge_errors) = pkg_component::sync_from_dir($pkg_alias, $tmp_dir, '', (int) $row['id']);

    // Modify upgrade versions, as needed
    if ($vars['version'] != $row['version'] && $urow = db::get_row("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'opn' ORDER BY id DESC LIMIT 0,1", $pkg_alias)) { 
        $old_upgrade_dir = SITE_PATH . '/etc/' . $pkg_alias . '/upgrades/' . $urow['version'];
        $upgrade_dir = SITE_PATH . '/etc/' . $pkg_alias . '/upgrades/' . $vars['version'];

        // Rename directory
        rename($old_upgrade_dir, $upgrade_dir);

        // Modify upgrade.php file, as needed
        $php_code = file_get_contents("$upgrade_dir/upgrade.php");
        $text_from = 'upgrade_' . $pkg_alias . '_' . str_replace('.', '_', $row['version']);
        $text_to = 'upgrade_' . $pkg_alias . '_' . str_replace('.', '_', $vars['version']);

        // Replace text
        $php_code = str_replace($text_from, $text_to, $php_code);
        file_put_contents("$upgrade_dir/upgrade.php", $php_code);
    }

    // Clean up
    db::query("UPDATE internal_packages SET version = %s WHERE alias = %s", $vars['version'], $pkg_alias);
    io::remove_dir($tmp_dir);

    // Set response
    $response = "The package $pkg_alias has been successfully merged due to new upgrades that were detected.\n";
    if (count($merge_errors) > 0) { 
        $response .= "\nHowever, some files were unable to be automatically merged and are listed below:\n\n";
        foreach ($merge_errors as $file) { 
            $response .= "\t$file\n";
        }
    }

    // Return response
    return $response;

}


}








