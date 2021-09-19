<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\App;
use Apex\App\Cli\Cli;
use Apex\App\Cli\Helpers\{NetworkHelper, PackageHelper};
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Network\Svn\SvnInstall;
use Apex\App\Sys\Install\{RedisInstaller, DatabaseInstaller, ApexInstaller, Installer};
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\Db\Interfaces\DbInterface;
use redis;

/**
 * YAML Installer
 */
class YamlInstaller
{

    /**
     * Process installation
     */
    public static function process():void
    {

        // Initialize
        global $argv;
        $file = file_exists(SITE_PATH . '/install.yml') ? 'install.yml' : 'install.yaml';
        $cli = new Cli();
        $cli->orig_argv = $argv;

        // Parse file
        try {
            $yaml = Yaml::parseFile(SITE_PATH . '/' . $file);
        } catch (ParseException $e) { 
            die("Unable to parse $file file -- " . $e->getMessage());
        }

        // Set variables
        ApexInstaller::$domain_name = $yaml['domain_name'] ?? 'localhost';
        ApexInstaller::$enable_admin = isset($yaml['enable_admin']) && $yaml['enable_admin'] == 0 ? 0 : 1;
        ApexInstaller::$enable_javascript = isset($yaml['enable_javascript']) && $yaml['enable_javascript'] == 0 ? 0 : 1;

        // Get redis info
        $redis = $yaml['redis'] ?? [];
        RedisInstaller::$info = [
            'host' => $redis['host'] ?? 'localhost',
            'port' => $redis['port'] ?? 6379,
            'password' => $redis['password'] ?? '',
            'dbindex' => $redis['dbindex'] ?? '0'
        ];

        // Connect to redis
        $redis = RedisInstaller::setup($cli);

        // Setup database
        self::setupSqlDatabase($yaml, $cli, $redis);

        // Complete install
        $app = ApexInstaller::complete($cli, $redis, true);

        // Install any necessary repos
        self::installRepos($yaml, $app);

        // Install packages
        self::installPackages($yaml, $app, $cli);

        // Set config vars
        self::setConfigVars($yaml, $app);

        // Check for installation image
        $opt = $cli->getArgs(['image']);
        if (isset($opt['image']) && $opt['image'] != '') { 
            ImageInstaller::install($opt['image'], $app, $cli);
        }

        // Welcome message
        Installer::welcomeMessage($cli, $app);
        exit(0);
    }

    /**
     * Setup SQL datbase
     */
    private static function setupSqlDatabase(array $yaml, Cli $cli, redis $redis):void
    {

        // Check if slave server
        $opt = $cli->getArgs();
        $is_slave = $opt['slave'] ?? false;
        if ($is_slave === true) { 
            return;
        }

        // Check we have database info
        $db = $yaml['db'] ?? [];
        if (!isset($db['dbname'])) { 
            die("No database name exists within the YAML file.");
        } elseif (!isset($db['user'])) { 
            die("No database user exists within the YAML file.");
        }

        // Load bootstrap file
        $cntr_items = require(SITE_PATH . '/boot/container.php');
        DatabaseInstaller::$db_class = $cntr_items[DBInterface::class];

        // Get driver
        $parts = explode("\\", DatabaseInstaller::$db_class);
        $db_driver = array_pop($parts);

        // Set database information
        DatabaseInstaller::$connection_info = [
            'dbname' => $db['dbname'],
            'user' => $db['user'],
            'password' => $db['password'] ?? '',
            'host' => $db['host'] ?? 'localhost',
            'port' => $db['port'] ?? DatabaseInstaller::$default_ports[$db_driver]
        ];

        // Setup database
        DatabaseInstaller::setup($cli, $redis);
    }

    /**
     * Install repos
     */
    public static function installRepos(array $yaml, App $app):void
    {

        // Get repos
        $repos = $yaml['repos'] ?? [];
        if (count($repos) == 0) { 
            return;
        }
        $helper = $app->getContainer()->make(NetworkHelper::class);

        // Go through repos
        foreach ($repos as $host) { 
            $helper->addRepo($host);
        }

    }

    /**
     * Install packages
     */
    public static function installPackages(array $yaml, App $app, Cli $cli):void
    {

        // Initialize
        $install_queue = [];
        $packages = $yaml['packages'] ?? [];
        $pkg_helper = $app->getContainer()->make(PackageHelper::class);
        $svn_install = $app->getContainer()->make(SvnInstall::class);

        // Get repo
        $repo_store = $app->getContainer()->make(ReposStore::class);
        $repo = $repo_store->get('apex');

        // Generate installation queue
        foreach ($packages as $pkg_alias => $version) { 
            $pkg_alias = $pkg_helper->getSerial($pkg_alias);
            if (!$pkg = $pkg_helper->checkPackageAccess($repo, $pkg_alias, 'can_read', true)) { 
                $cli->error("You do not have access to download the package '$pkg_alias'");
                continue;
            }
            $install_queue[] = $pkg;
        }

        // Go through install queue
        foreach ($install_queue as $pkg) { 

            // Install
            $svn = $pkg->getSvnRepo();
            $svn_install->process($svn);

            // Success message
            $cli->send("Successfully installed the package, " . $pkg->getAlias() . ".\r\n\r\n");
        }

    }

    /**
     * Set config vars
     */
    public static function setConfigVars(array $yaml, App $app):void
    {

        // Go through config vars
        $config = $yaml['config'] ?? [];
        foreach ($config as $key => $value) { 
            $app->setConfigVar($key, $value);
        }

    }

}



