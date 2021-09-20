<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create html tag
 */
class HtmlTag implements CliCommandInterface
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
        $file = $this->opus->buildClass('html_tag', $filename, '', SITE_PATH);

        // Success message
        $cli->success("Successfully created new HTML tag which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate HTML tag',
            usage: 'opus html-tag <FILENAME>',
            description: 'Generate a new HTML tag class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new HTML tag class, relative to the /src/ directory.');
        $help->addExample('./apex opus html-tag MyShop/Tags/SomeTag'); 

        // Return
        return $help;
    }


}

