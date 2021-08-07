<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\App;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Set config
 */
class SetConfig implements CliCommandInterface
{

    #[Inject(App::class)]
    private App $app;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $name = $args[0] ?? '';
        $value = $args[1] ?? '';

        // Check
        if ($name == '' || $value == '') { 
            $cli->error("You did not specify a configuration variable name and value.  See 'apex help sys set-config' for details.");
            return;
        } elseif (!$this->app->hasConfig($name)) { 
            $cli->error("Configuration variable does not exist, $name");
            return;
        }

        // Update
        $this->app->setConfigVar($name, $value);

        // Success
        $cli->send("Successfullyy updated the configuration variable '$name' to:\r\n\r\n    $value\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Set Configuration Variable', 
            usage: 'sys set-config <NAME> <VALUE>',
            description: 'Update the value of an existing configuration variable.',
            params: [
                'name' => 'The name of the confirmation variable to update, formatted as PACKAGE.ALIAS',
                'value' => 'The value to update the configuration variable to.'
            ], 
            examples: [
                './apex sys set-config mypackage.tax_price 7.95',
                './apex sys set-config blog-pkg.default_category reviews'
            ]
        );

        // Return
        return $help;
    }

}


