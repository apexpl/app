<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Migration;

use Apex\Svc\Db;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Adapters\MigrationsConfig;
use Apex\Migrations\Handlers\{ClassManager, Installer};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Migrate
 */
class Migrate implements CliCommandInterface
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(MigrationsConfig::class)]
    private MigrationsConfig $config;

    #[Inject(ClassManager::class)]
    private ClassManager $manager;

    #[Inject(Installer::class)]
    private Installer $installer;

    /**
     * Process
     */
    public Function process(Cli $cli, array $args):void
    {

        // Initialize
        if (!list($package, $name) = $this->getOptions($cli)) { 
            return;
        }

        // Migrate as necessary
        if ($name != '') { 
            $secs = $this->installer->installMigration($package, $name);
            $cli->send("Installed migration $name from package '$package' in " . $secs . "ms\n\n");

        } elseif ($package == '') { 
            $this->installer->migrateAll();
        } elseif (!$this->installer->migratePackage($package)) { 
            $cli->send("Nothing to do.  Package '$package' is up to date.\n");
        }

    }

    /**
     * Get options
     */
    private function getOptions(Cli $cli):?array
    {

        // Get options
        $opt = $cli->getArgs(['package', 'name']);
        $package = $opt['package'] ?? '';
        $name = $opt['name'] ?? '';
        $table_name = $this->config->getTableName();

        // Check package exists, if defined
        if ($package != '' && !$pkg = $this->config->getPackage($package)) { 
            $cli->send("Package does not exist '$package'.  No migrations installed.\n");
            return null;
        }

        // Search name within all packages
        if ($name != '' && $package == '') { 

            if (!$packages = $this->manager->searchByName($name)) { 
                $cli->send("The migration '$name' does not exist within any packages.  No migrations installed.\n");
                return null;
            } elseif (count($packages) > 1) { 
                $cli->send("The migration '$name' exists in more than one package.  Please use the --package option to specify which package to install from.  No migrations installed.\n");
                return null;
            }
            $package = $packages[0];

        // Check name with package defined
        } elseif ($name != '' && !file_exists($pkg[0] . '/' . $name . '/migrate.php')) {  
            $cli->send("No migration exists with package '$package' and name '$name'.  No migrations installed.\n");
            return null;
        }

        // Check if already installed
        if ($name != '' && $package != '' && $row = $this->db->getRow("SELECT * FROM $table_name WHERE package = %s AND class_name = %s", $package, $name)) { 
            $cli->send("The migration for package '$package' with name '$name' has already been installed, and can not be installed again.\n");
            return null;
        }

        // Return
        return [$package, $name];
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Install Pending Migrations',
            usage: 'migration migrate [--package=] [--name=]',
            description: 'Install any needed pending database migrations.'
        );

        // Add flags
        $help->addFlag('--package', 'Optional package alias to install migrations for.');
        $help->addParam('--name', 'Optional name / alias of migration to install.');
        $help->addExample('./apex migration migrate');
        $help->addExample('./apex migration migrate --package myshop');

        // Return
        return $help;
    }


}




