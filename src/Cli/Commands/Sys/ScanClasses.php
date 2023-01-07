<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\Container;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\ScanListeners as Scanner;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Scan listeners
 */
class ScanClasses implements CliCommandInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Scan
        $scanner = $this->cntr->make(\Apex\App\Sys\Utils\ScanClasses::class);
        $scanner->scan();

        // Success
        $cli->send("Successfully scanned all classes and updated routing keys and child classes.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Scan Listeners',
            usage: 'sys scan-listeners',
            description: "Scans all files within the /src/ directory and updates the routing map as necessary.  Use this command when you update the 'routing_key' property of a listener."
        );
        $help->addExample('./apex sys scan-listeners');

        // Return
        return $help;
    }

}


