<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Pkg\Filesystem\Rollback\Rollback as Processor;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Rollback
 */
class Rollback implements CliCommandInterface
{

    #[Inject(Processor::class)]
    private Processor $rollback;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Check for previously installed upgrades
        if (!file_exists(SITE_PATH . '/.apex/upgrades/installs.json')) { 
            $cli->error("No previous upgrades have been installed on this system.  Nothing to do.");
            return;
        }
        $installs = json_decode(file_get_contents(SITE_PATH . '/.apex/upgrades/installs.json'), true);
        krsort($installs);

        // Create options
        $options = [];
        foreach ($installs as $secs => $packages) { 

            // Create line
            $line = date('M-d H:i', $secs) . ' -- ';
            foreach ($packages as $alias => $version) { 
                $line .= $alias . ' v' . $version . ', ';
            }
            $options[$secs] = preg_replace("/, $/", "", $line);
            if (count($options) >= 10) { 
                break;
            }
        }

        // Ensure we have options
        if (count($options) == 0) { 
            $cli->send("There are no previously installed upgrades on this system.  Nothing to do.\r\n\r\n");
            return;
        }

        // Get options
        $opt = $cli->getOption("Below shows the most recent upgrades installed.  Please select which point you would like to rollback the system to.", $options, '', true);
        $packages = $installs[$opt];

        // Confirm rollback
        if (!$this->confirmRollback($cli, $packages)) { 
            $cli->send("\r\nOk, goodbye.\r\n\r\n");
            return;
        }

        // Perform rollback
        foreach ($installs as $secs => $packages) { 

            // Go through packages
            foreach ($packages as $pkg_alias => $version) { 
                $this->rollback->process($pkg_alias, $version);
            }

            // Remove transaction
            unset($installs[$secs]);

            // Quit, if we're done
            if ((string) $secs == $opt) { 
                break;
            }
        }

        // Save upgrades.json file
        $json = json_encode($installs, JSON_PRETTY_PRINT);
        file_put_contents(SITE_PATH . '/.apex/upgrades/installs.json', $json);

        // Success
        $cli->send("\r\n");
        $cli->send("Successfully rolled back the system.\r\n\r\n");
    }

    /**
     * Confirm rollback
     */
    private function confirmRollback(Cli $cli, array $packages):bool
    {

        // Initialize
        $cli->send("\r\n");
        $cli->send("The system will be rolled back to the following packages and versions:\r\n\r\n");

        // Go through packages
        foreach ($packages as $alias => $version) { 

            // Check for config.json
            $config_file = SITE_PATH . '/.apex/upgrades/' . $alias . '/' . $version . '/config.json';
            if (!file_exists($config_file)) { 
                continue;
            }
            $conf = json_decode(file_get_contents($config_file), true);

            $cli->send("    $alias v" . $conf['from_version'] . "\r\n");
        }

        // Confirm
        $cli->send("\r\n");
        return $cli->getConfirm("Are you sure you wish to revert the system?");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Rollback Upgrades',
            usage: 'package rollback',
            description: 'Rollback previousy installed upgrades, restoring your system to its previous state.'
        );

        // Return
        return $help;
    }

}

