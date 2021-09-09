<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create API endpoint
 */
class ApiEndpoint implements CliCommandInterface
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
        $filename = $this->helper->parseFilename($filename);

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
        $file = $this->opus->buildClass('api_endpoint', $filename, '', SITE_PATH);

        // Success message
        $cli->success("Successfully created new API endpoint which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate API Endpoint',
            usage: 'opus api-endpoint <FILENAME>',
            description: 'Generate a new API endpoint class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new API endpoint class, relative to the /src/ directory.');
        $help->addExample('./apex opus api-endpoint MyShop/Api/Invoices/List');

        // Return
        return $help;
    }


}


