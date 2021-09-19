<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\Svc\{Convert, Db};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\BaseModelInterface;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Create model
 */
class Model implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['dbtable','type']);
        $filename = trim(($args[0] ?? ''), '/');
        $dbtable = $opt['dbtable'] ?? '';
        $type = $opt['type'] ?? 'php8';
        $magic = $opt['magic'] ?? false;

        // Format filename
        if (!preg_match("/^src\//", $filename)) { 
            $filename = 'src/' . ltrim($filename, '/');
        }
        if (!preg_match("/\.php$/", $filename)) { 
            $filename .= '.php';
        }

        // Perform checks
        if ($type != 'eloquent' && ($dbtable == '' || !$this->db->checkTable($dbtable))) { 
            $cli->error("Database table does not exist, $dbtable.  Please ensure to use the '--dbtable' option.");
            return;
        } elseif (!in_array($type, ['php8', 'php7', 'eloquent', 'doctrine'])) { 
            $cli->error("Invalid type, $type.  Supported values are: php8, php7, eloquent, doctrine");
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
        $files = $this->opus->buildModel($filename, SITE_PATH, $dbtable, $type, $magic);

        // Add to redis
        foreach ($files as $file) {
            $class_name = $this->opus_helper->pathToNamespace($file);
            $this->redis->sadd('config:interfaces:' . BaseModelInterface::class, $class_name);
        }

        // Success message
        $cli->success("Successfully created new model from database table '$dbtable' which is now available at:", $files);
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


