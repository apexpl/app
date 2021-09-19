<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\EmailNotificationControllerInterface;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Create e-mail controller
 */
class EmailController implements CliCommandInterface
{

    #[Inject(OpusHelper::class)]
    private OpusHelper $helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(redis::class)]
    private redis $redis;

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
        $file = $this->opus->buildClass('email_controller', $filename, '', SITE_PATH);

        // Add to redis
        $class_name = $this->helper->pathToNamespace($file);
        $this->redis->sadd('config:interfaces:' . EmailNotificationControllerInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new e-mail controller which is now available at:", [$file]);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate E-Mail Controller',
            usage: 'opus email-controller <FILENAME>',
            description: 'Generate a new e-mail controller class.'
        );

        // Params
        $help->addParam('filename', 'File location of the new e-mail controller class, relative to the /src/ directory.');
        $help->addExample('./apex opus email-controller MyShop/Controllers/ShopNotifications'); 

        // Return
        return $help;
    }


}


