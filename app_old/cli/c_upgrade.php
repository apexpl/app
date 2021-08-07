<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\db;
use apex\app\cli\cli;
use apex\app\pkg\upgrade;
use apex\app\exceptions\{ApexException, PackageException, UpgradeException};


/**
 * Handles all upgrade commands within the Apex CLI client.
 */
class c_upgrade
{


/**
 * Create upgrade point
 */
public function create(array $vars):string
{ 

    // Set variables
    $pkg_alias = $vars[0] ?? '';
    $version = $vars[1] ?? '';
    debug::add(4, tr("CLI: Starting to create upgrade point for package: {1}", $pkg_alias), 'info');

    // Check package
    if ($pkg_alias == '') { 
        throw new PackageException('undefined');
    }

    // Check for open upgrades
    $count = db::get_field("SELECT count(*) FROM internal_upgrades WHERE package = %s AND status = 'open'", $pkg_alias);
    if ($count > 0) { 
        $ok = cli::get_input("There are currently open upgrades already on this package.  Are you sure you want to create another upgrade point? (y/n) [n]", 'no');
        if (strtolower($ok) != 'y') { 
            return "Ok, goodbye.";
        }
    }

    // Create upgrade
    $client = make(upgrade::class);
    $upgrade_id = $client->create($pkg_alias, $version);
    debug::add(4, tr("CLI: Completed vreating upgrade point for package: {1}", $pkg_alias), 'info');

    // Return response
    $version = db::get_field("SELECT version FROM internal_upgrades WHERE id = %i", $upgrade_id);
    return "Successfully created upgrade point on package $vars[0] to upgrade version $version\n\n";

}

/**
 * Publish
 */
public function publish(array $vars)
{ 

    // Set variables
    $pkg_alias = $vars[0] ?? '';

    // Get package
    if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $vars[0])) { 
        throw new PackageException('not_exists', $vars[0]);
    } elseif (!$open_rows = db::query("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'open'", $pkg_alias)) {  
        throw new PackageException('no_open_upgrades', $pkg_alias);
    }

    // Ask which upgrade to publish
    if (count($open_rows) > 1) { 
        cli::send("More than one open upgrade was found for this package.  Please specify which upgrade you would like to publish.\n\n");

        // Display open upgrades
        $available = array(); $x=1;
        foreach ($open_rows as $row) { 
            cli::send("    [$x] v$row[version]\n");
            $available[$x] = $row['id'];
        $x++; }

        // Get upgrade to publish
        $num = cli::get_input("Upgrade to publish: ");
        if (!isset($available[$num])) { 
            return "Ok, goodbye.\n";
        }

        // Get upgrade row
        if (!$upgrade = db::get_idrow('internal_upgrades', $available[$num])) { 
            throw new UpgradeException('not_exists', $available[$num]);
        }

    // Get one available upgrade
    } else { 
        $upgrade = db::get_row("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'open' LIMIT 0,1", $pkg_alias);
    }

    // Publish upgrade
    $client = make(upgrade::class);
    $is_git = $client->publish((int) $upgrade['id']);

    // Debug
    debug::add(4, tr("CLI: Completed publishing upgrade point for package: {1}", $pkg_alias), 'info');

    // Set response
    $response = "Successfully published the appropriate upgrade for package, $pkg_alias\n\n";
    if ($is_git == 1) { 
        $response .= "To complete the upgrade, and publish to the git repository, run the following command:\n";
        $response .= "\tcd " . SITE_PATH . "/src/$pkg_alias/git; ./git.sh\n\n";
    }

    // Ask to create new upgrade point
    $ok = $vars[1] ?? '';
    if ($ok == '') { 
        $ok = cli::get_input("Would you like to create a new upgrade point? (y/n) [y]: ", 'y');
    }

    // Create upgrade point, if needed
    if (strtolower($ok) == 'y'){
        $response .= $this->create(array($pkg_alias));
    }

    // Return
    return $response;

}

/**
 * Check for available upgrades
 */
public function check(array $vars, network $client)
{ 

    // Check upgrades
    $upgrades = $client->check_upgrades($vars);

    // Go through upgrades
    $results = '';
    foreach ($upgrades as $pkg_alias => $version) { 

        // Get package
        if (!$row = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
            continue;
        }

        // Add to results
        $results .= '[' . $pkg_alias . '] ' . $row['name'] . ' v' . $version . "\n";
    }

    // Give response
    if ($results == '') { 
        $response = "No upgrades were found for any installed packages.\n";
    } else { 
        $response = "The following available upgrades were found:\n\n";
        $response .= $results . "\n";
        $response .= "If desired, you may install the upgrades with the command: ./apex upgrade\n";
    }
    debug::add(4, "CLI: check_upgrades done", 'info');

    // Return
    return $response;

}

public function rollback($vars)
{

    // Checks
    $pkg_alias = $vars[0] ?? '';
    $version = $vars[1] ?? '';

    // Get package
    if (!$pkg = db::get_row("SELECT * FROM internal_packages WHERE alias = %s", $pkg_alias)) { 
        throw new PackageException('not_exists', $pkg_alias);
    }

    // Get rollback version, if needed
    if ($version == '') { 

        // Send message
        $x=1; $prev_versions = array();
        cli::send("Previously installed upgrades:\n\n");

        // Go through previously installed upgrades
        $rows = db::query("SELECT * FROM internal_upgrades WHERE package = %s AND status = 'installed' AND prev_version != '' ORDER BY id DESC LIMIT 0,25", $pkg_alias);
        foreach ($rows as $row) { 
            cli::send("    [$x] v" . $row['prev_version'] . "\n");
            $prev_versions[(string) $x] = $row['prev_version'];
        $x++; }

        // Get rollback version
        $id = cli::get_input("Enter version to rollback to: ");
        if (!isset($prev_versions[$id])) { 
            return "Invalid option, goodbye\n";
        }
        $version = $prev_versions[$id];
    }
    debug::add(2, tr("Starting to rollback package {1} to version {2}", $pkg_alias, $version));

    // Rollback package
    $upgrade = make(upgrade::class);
    $upgrade->rollback($pkg_alias, $version);

    // Return
    debug::add(1, tr("Successfully rolled back package {1} to version {2}", $pkg_alias, $version));
    return "Successfully completed rollback on package $pkg_alias to version $version\n";

}



