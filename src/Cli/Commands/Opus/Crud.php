<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\App\Interfaces\BaseModelInterface;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * CRUD Generation
 */
class Crud implements CliCommandInterface
{

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['dbtable', 'view']);
        $filename = trim(($args[0] ?? ''), '/');
        $dbtable = $opt['dbtable'] ?? '';
        $view = $opt['view'] ?? '';
        $with_magic = $opt['magic'] ?? false;

        // Parse filename
        $filename = $this->opus_helper->parseFilename($filename);
        $class_name = $this->opus_helper->pathToNamespace($filename);

        // Perform checks
        if ($dbtable == '') {
            $cli->error("No --dbtable flag specified, which is required.");
            return;
        } elseif (file_exists(SITE_PATH . '/' . $filename)) { 

            // Check if it's an Apex model
            $obj = new \ReflectionClass($class_name);
            if (!$obj->implementsInterface(BaseModelInterface::class)) {
                $cli->error("The existing model was found, but it is not an Apex model hence can not be used.");
                return;
            }
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
        $files = $this->opus->buildCrud($filename, $dbtable, $view, $with_magic, SITE_PATH);

        // Success message
        $cli->success("Successfully generated CRUD files for database table '$dbtable', which are now located at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate CRUD Files',
            usage: 'opus crud <FILENAME> --dbtable <TABLE> [--view <PATH>] [--magic]',
            description: 'Will generate the necessary PHP library, data table and form components, and views for basic CRUD operations of a database table.'
        );

        $help->addParam('filename', 'Location of the PHP model class to generate, relative to the /src/ directory.');
        $help->addFlag('--dbtable', 'The database table name to generate CRUD controller for.');
        $help->addFlag('--view', 'Path of the view to generate.');
        $help->addFlag('--magic', 'Whether or not to use magic get / set methods within the model.');
        $help->addExample('./apex opus crud MyShop/ProductController --dbtable products --view admin/shop/products');

        // Return
        return $help;
    }

}



