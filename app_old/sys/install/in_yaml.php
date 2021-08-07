<?php
declare(strict_types = 1);

namespace apex\app\sys\install;

use apex\app;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;






public function process_yaml_file()
{

    // Initialize
    $file = file_exists(SITE_PATH . '/install.yml') ? 'install.yml' : 'install.yaml';

    // Parse file
    try {
        $vars = Yaml::parseFile(SITE_PATH . '/' . $file);
    } catch (ParseException $e) { 
        die("Unable to parse $file file -- " . $e->getMessage());
    }

    // Get server type
    $this->server_type = $vars['server_type'] ?? 'all';
    if (!in_array($this->server_type, array('all', 'web', 'app', 'dbs', 'dbm'))) {
        die("Invalid server type defined within install.yml file, $this->server_type");
    }

    // Get domain name
    $this->domain_name = $vars['domain_name'] ?? '';
    if ($this->domain_name == '') { 
        die("No domain specified within install.yml file");
    }

    // Get other basic variables
    $this->enable_admin = isset($vars['enable_admin']) && $vars['enable_admin'] == 0 ? 0 : 1;
    $this->enable_javascript = isset($vars['enable_javascript']) && $vars['enable_javascript'] == 0 ? 0 : 1;

    // Get redis info
    $redis = $vars['redis'] ?? [];
    $this->redis_host = $redis['host'] ?? 'localhost';
    $this->redis_port = $redis['port'] ?? 6379;
    $this->redis_pass = $redis['password'] ?? '';
    $this->redis_dbindex = $redis['dbindex'] ?? '0';

    // Connect to redis
    $this->connect_redis();

    // Get mySQL database info, if needed
    if (!redis::exists('config:db_master')) { 

        // Get database driver
        $db = $vars['db'] ?? [];
        $this->db_driver = $db['driver'] ?? 'mysql';
        if (!file_exists(SITE_PATH . '/src/app/db/' . $this->db_driver . '.php')) { 
            die("Invalid database driver, $this->db_driver");
        }

        // Set database variables
        $this->has_mysql = true;
        $this->type = isset($db['autogen']) && $db['autogen'] == 1 ? 'quick' : 'standard';
        $this->dbname = $db['dbname'] ?? '';
        $this->dbuser = $db['user'] ?? '';
        $this->dbpass = $db['password'] ?? '';
        $this->dbhost = $db['host'] ?? 'localhost';
        $this->dbport = $db['port'] ?? 3306;
        $this->dbroot_password = $db['root_password'] ?? '';
        $this->dbuser_readonly = $db['readonly_user'] ?? '';
        $this->dbpass_readonly = $db['readonly_password'] ?? '';

        // Generate random passwords, as needed
        if ($this->type == 'quick') { 
            $this->dbpass = io::generate_random_string(24);
            $this->dbpass_readonly = io::generate_random_string(24);
        }

        // Complete mySQL setup
        $this->complete_mysql();
    }

    // Complete installation
    $this->complete_install();

    // Add repos
    $repos = $vars['repos'] ?? [];
    foreach ($repos as $host => $repo_vars) { 
        $username = $repo_vars['user'] ?? '';
        $password = $repo_vars['password'] ?? '';

        // Add repo
        $client = app::make(repo::class);
        $client->add($host, $username, $password);
    }

    // Install packages
    $packages = $vars['packages'] ?? [];
    foreach ($packages as $alias) { 
        $client = app::make(package::class);
        $client->install($alias);
    }

    // Install themes
    $themes = $vars['themes'] ?? [];
    foreach ($themes as $alias) { 
        $client = app::make(theme::class);
        $client->install($alias);
    }

    // Configuration vars
    $config = $vars['config'] ?? [];
    foreach ($config as $key => $value) { 
        app::update_config_var($key, $value);
    }

    // Welcome message, and exit
    $this->welcome_message();
    exit(0);

}



