<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Svn\SvnUpgrade;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Upgrade package
 */
class Upgrade implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(SvnUpgrade::class)]
    private SvnUpgrade $svn_upgrade;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get options
        $opt = $cli->getArgs();
        $confirm = $opt['confirm'] ?? false;
        $noverify = $opt['noverify'] ?? false;

        // Get install queue
        $install_queue = $this->getInstallQueue($cli, $args);

        // Go through install queue
        $installed = [];
        foreach ($install_queue as $pkg) { 

            // Check
            if ($pkg->isLocal() === true || !$pkg->getSvnRepo()) { 
                continue;
            }

            // Install upgrade
            if (!$version = $this->svn_upgrade->process($pkg, $confirm, $noverify)) { 
                continue;
            }
            $cli->send("Successfully upgraded the package " . $pkg->getSerial() . " to v$version.\r\n\r\n");
            $installed[$pkg->getAlias()] = $version;
        }

        // Save to upgrade registry
        $this->saveUpgradeTransaction($installed);

    }

    /**
     * Get install queue
     */
    private function getInstallQueue(Cli $cli, array $args):array
    {

        // Single package upgrade
        $install_queue = [];
        if (count($args) > 0) { 

            foreach ($args as $pkg_alias) { 
                if (!$pkg = $this->pkg_helper->get($pkg_alias)) { 
                    return [];
                }
                $install_queue[] = $pkg;
            }

        // All packages
        } else { 

            $packages = $this->pkg_store->list();
            foreach ($packages as $pkg_alias => $vars) { 
                $install_queue[] = $this->pkg_store->get($pkg_alias);
            }
        }

        // Return
        return $install_queue;
    }

    /**
     * Save upgrade transaction
     */
    private function saveUpgradeTransaction(array $installed):void
    {

        // Check for zero installed
        if (count($installed) == 0) { 
            return;
        }

        // Get existing json, if exists
        $json = [];
        $json_file = SITE_PATH . '/.apex/upgrades/installs.json';
        if (file_exists($json_file)) { 
            $json = json_decode($json_file, true);
        }

        // Add installs
        $secs = time();
        $json[$secs] = $installed;

        // Save file
        file_put_contents($json_file, json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Upgrade Package(s)',
            usage: 'package upgrade [PKG_ALIAS>] [--confirm] [--noverify]',
            description: 'Upgrade one or all packages installed on the local machine to the latest version.'
        );

        $help->addParam('pkg_alias', 'Optional one or more package aliases to upgrade.  If left blank, all packages installed on the local machine will be checked for available upgrades.');
        $help->addFlag('--confirm', 'If present, will auto-confirm and install upgrades that are marked to contain breaking changes.');
        $help->addFlag('--noverify', 'If present, verification of the digital sigatures will not be performed.');
        $help->addExample('./apex package upgrade');

        // Return
        return $help;
    }

}


