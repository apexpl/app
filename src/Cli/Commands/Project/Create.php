<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, AccountHelper};
use Apex\App\Pkg\Filesystem\Package\Installer;
use Apex\App\Network\Stores\{PackagesStore, ReposStore};
use Apex\App\Pkg\ProjectManager;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Create project
 */
class Create implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(ProjectManager::class)]
    private ProjectManager $manager;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(Installer::class)]
    private Installer $installer;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get alias
        $opt = $cli->getArgs(['repo']);
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $repo_alias = $opt['repo'] ?? 'apex';
        $is_staging = $opt['staging'] ?? false;

        // Initial check
        if (is_dir(SITE_PATH . '/.svn')) { 
            $cli->error("A project has already been created on this system, and it is already under version control.");
            return;
        }

        // Check for author
        $author = '';
        if (preg_match("/^([a-zA-z0-9_-]+)\/([a-zA-Z0-9_-]+)$/", $pkg_alias, $match)) { 
            $author = $match[1];
            $pkg_alias = $match[2];
        }

        // Check
        if ($pkg_alias == '' || !preg_match("/^[a-zA-z0-9_-]+/", $pkg_alias)) { 
            $cli->error("Invalid project alias, $alias");
            return;
        } elseif ($pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package already exists on the local machine with alias, $pkg_alias");
            return;
        } elseif (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with alias, $repo_alias");
            return;
        } elseif (!$this->pkg_helper->checkDuplicate($pkg_alias, $repo)) { 
            return;
        }

        // Close any open packages
        if (!$this->closePackages($cli)) { 
            return;
        }

        // Get account
        $acct = $this->acct_helper->get();
        if ($author == '') { 
            $author = $acct->getUsername();
        }

        // Check for staging environment
        if ($is_staging === false) {

            $staging_url = 'https://' . $pkg_alias . '.' . $author . '.' . $repo->getStagingHost();
            $cli->send("\r\n\r\n");

            $cli->send("You may optionally create a staging environment for this project, meaning a live system that will always be in-sync with all commits will be available at:\r\n\r\n");
            $cli->send("    $staging_url\r\n\r\n");
            $is_staging = $cli->getConfirm("Would you like to create a staging environment for this project?", 'n');
        }

        // Create project
        $pkg = $this->manager->create($pkg_alias, $repo, $acct, $author);  

        // Create staging environment, if needed
        if ($is_staging === true) { 
            $dbinfo = $this->manager->createStagingEnvironment($pkg);
        }
        $pkg_serial = $pkg->getSerial();

        // Start project info
        $project_info = [
            'pkg_alias' => $pkg->getAlias(),
            'is_staging' => $is_staging === true ? 1 : 0
        ];

        // Success message
        $cli->sendHeader('Successfully Created Project');
        $cli->send("The new project '" . $pkg->getSerial() . "' has been successfully created.  The entire Apex directory is now under version control, and its repository may be found at:\r\n\r\n");
        $cli->send("    Web: https://" . $repo->getHttpHost() . "/$pkg_serial\r\n");
        $cli->send("    SVN: svn://" . $repo->getSvnHost() . "/$pkg_serial/trunk\r\n\r\n");

        // Add staging environment message
        if ($is_staging === true) { 
            $staging_url = "http://" . $pkg->getAlias() . '.' . $pkg->getAuthor() . '.' . $repo->getStagingHost() . '/';
            $dbname = str_replace('-', '_', ($pkg->getAuthor() . '_' . $pkg->getAlias()));

            // Add to project info
            $project_info['dbname'] = $dbinfo['dbname'];
            $project_info['user'] = $dbinfo['user'];
            $project_info['password'] = $dbinfo['password'];
            $project_info['host'] = $dbinfo['host'];
            $project_info['port'] = $dbinfo['port']; 

            // Send message
            $cli->send("A new staging environment has also been created, which may be accessed at:\r\n\r\n");
            $cli->send("    $staging_url\r\n\r\n");
            $cli->send("Information to remotely connect to the SQL database is as follows:\r\n\r\n");
            $cli->send("    Name:  $dbinfo[dbname]\r\n");
            $cli->send("    User:  $dbinfo[user]\r\n");
            $cli->send("    Pass:  $dbinfo[password]\r\n");
            $cli->send("    Host:  $dbinfo[host]\r\n");
            $cli->send("    Port:  $dbinfo[port]\r\n\r\n");
            $cli->send("Alternatively, you may easily connect to the remote SQL database anytime by running the following command without any arguments:\r\n\r\n");
            $cli->send("    apex project sql\r\n\r\n");
        }

        // Add project info to redis
        $this->redis->hmset('config:project', $project_info);
    }

    /**
     * Close any open packages
     */
    private function closePackages(Cli $cli):bool
    {

        // Check for open packages
        if (!is_dir(SITE_PATH . '/.apex/svn')) { 
            return true;
        }
        $dirs = scandir(SITE_PATH . '/.apex/svn');
        if (count($dirs) < 3) { 
            return true;
        }

        // Go through packages
        $cli->send("The following packages are currently open and under version control:\r\n\r\n");
        foreach ($dirs as $pkg_alias) { 
            if (in_array($pkg_alias, ['.', '..'])) { continue; }
            $cli->send("    $pkg_alias\r\n");
        }
        $cli->send("\r\n");

        // Confirm
        if (!$cli->getConfirm("Close the above packages?", 'y')) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return false;
        }

        // Close packages
        foreach ($dirs as $pkg_alias) { 
            if (in_array($pkg_alias, ['.', '..'])) { continue; }

            // Load package
            if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
                continue;
            }

            // Close package
            if (!$this->installer->install($pkg)) {
                $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias; 
                $cli->send("The SVN directory at $svn_dir is not empty, and should be manually checked.  Package not successfully fully closed.\r\n\r\n");
            } else { 
                $cli->send("Successfully closed the package '$pkg_alias', and it is no longer version controlled.\r\n\r\n");
            }
        }

        // Return
        return true;
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Project',
            usage: 'project create <PROJECT>',
            description: 'Creates a new project, and if desired, staging environemnt on the repository.'
        );

        $help->addParam('project', 'The alias of the project / package to create.');
        $help->addExample('./apex project create my-client-site');

        // Return
        return $help;
    }

}


