<?php
declare(strict_types = 1);

namespace apex\app\cli;

use apex\app;
use apex\libc\{db, redis, debug};
use apex\app\cli\cli;
use apex\app\pkg\github;
use apex\app\exceptions\ApexException;


/**
 * Handles all git / Github functionality within the Apex CLI client.
 */
class c_git
{


/**
 * Git initialize repo
 */
ublic function init(array $vars):string
{

    // Initialize GIthub repo
    $client = make(github::class);
    $client->init($vars[0]);

    // Set response
    $response = "Successfully initialized local Github repo for package $vars[0].  To complete initialization, please run the following command in termina.\n\n";
    $response .= "      cd " . SITE_PATH . "/src/$vars[0]/git; ./git.sh\n\n";

    // Return
    return $response;

}

/**
    * Sync local code from GIthub repository.
 *
 * Downloads the GIthub repository for the specified package into a tmp 
 * directory, and copies / updates all local code within the local 
 * Apex installation with any newer code.
 */
public function sync(array $vars):string
{

    // Initialize
    $pkg_alias = strtolower($vars[0]);

    // Sync the repo
    $client = app::make(github::class);
    $client->sync($pkg_alias);

    // Return
    return "Successfully synced package with its git repository, $pkg_alias\n";


}

/**
 * Compare git repos.
 *
 * Downloads the remote git repo, compares it to the local filesystem, and generates the necessary 
 * git.sh file to add all necessary files to the next push / commit, and 
 * ensure the git repo is a mirror copy of the local filesystem.
 */
public function compare(array $vars):string
{

    // Initialize
    $pkg_alias = strtolower($vars[0]);

    // Compare git repo
    $client = app::make(github::class);
    $client->compare($pkg_alias);

    // Return
    return "Successfully compared git repo with local filesystem for package, $pkg_alias.  There is now a new file located at /src/$pkg_alias/git/git.sh, to be executd and will ensure the git repo is a mirror of the local filesystem.\n";

}


}



