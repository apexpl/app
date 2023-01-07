<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Create web socket listener
 */
class WsListener implements CliCommandInterface
{

    #[Inject(OpusHelper::class)]
    private OpusHelper $helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $filename = trim(($args[0] ?? ''), '/');

        // Perform checks
        if (file_exists(SITE_PATH . '/' . $filename)) { 
            $cli->error("File already exists at, $filename");
            return;
        }

        // Create parent directory, if needed
        $full_path = SITE_PATH . '/' . $filename;
        if (!is_dir(dirname($full_path)) && $cli->getConfirm("Parent directory does not exist at, " . dirname($full_path) . ".  Would you like to create it?", 'y') === true) { 
            mkdir(dirname($full_path), 0755, true);
        } elseif (!is_dir(dirname($full_path))) { 
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Build
        $file = $this->opus->buildClass('ws-listener', $filename, '', SITE_PATH);

        // Success message
        $cli->success("Successfully created new web socket listener which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate Web Socket Listener',
            usage: 'opus ws-listener <FILENAME>',
            description: 'Generate a new web socket listener class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new web socket listener class, relative to the /src/ directory.');

        // Examples
        $help->addExample('./apex opus ws-listener MyShop/InfuraListener');

        // Return
        return $help;
    }

}


