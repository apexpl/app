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
            title: 'Generate Model',
            usage: 'opus model <FILENAME> --dbtable=<TABLE> [--type=(php8|php7|eloquent|doctrine)] [--magic]',
            description: 'Generate a new model class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new model class, relative to the /src/ directory.');
        $help->addFlag('--dbtable', 'The name of the database table to use to generate property names.  Not required if generating Eloquent model.');
        $help->addFlat('--type', "Type of model to generate, defaults to 'php8'.  Supported values are: php8, php7, eloquent, doctrine");
        $help->addFlag('--magic', "No value, and only applicable if type is 'php8' or 'php7'.  If present, will generate model without hard coded get / set methods and instead use magic methods in place via extension.  Otherwise, will generate model with hard coded ge / set methods.");

        // Examples
        $help->addExample('./apex opus MyShop/Models/Products --dbtable shop_products --magic');
        $help->addExample('./apex opus model MyShop/Models/ShopOrder --type eloquent');

        // Return
        return $help;
    }


}


