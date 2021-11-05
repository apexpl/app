<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Create iterator
 */
class Iterator implements CliCommandInterface
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
        $opt = $cli->getArgs(['item_class']);
        $filename = trim(($args[0] ?? ''), '/');
        $item_class = $opt['item_class'] ?? '';

        // Parse item class
        $item_class = $this->helper->pathToNamespace($item_class);
        $filename = $this->helper->parseFilename($filename);

        // Perform checks
        if (!class_exists($item_class)) { 
            $cli->error("The item class does not exist at, $item_class.");
            return;
        } elseif (file_exists(SITE_PATH . '/' . $filename)) { 
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
        $file = $this->opus->buildClass('iterator', $filename, $item_class, SITE_PATH);

        // Success message
        $cli->success("Successfully created new iterator which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate Iterator',
            usage: 'opus iterator <FILENAME> --item_class=<ITEM_CLASS>',
            description: 'Generate a new iterator class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new iterator class, relative to the /src/ directory.');
        $help->addFlag('--item_class', 'The filepath to the item class.');

        // Examples
        $help->addExample('./apex opus iterator MyShop/ProductIterator --item_class Demo/Models/Product');

        // Return
        return $help;
    }


}


