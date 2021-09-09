<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Image;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create installation image
 */
class Create implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(AccountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $alias = $this->convert->case(($args[0] ?? ''), 'lower');
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid image alias, $alias");
            return;
        }

        // Check if already exists
        if (file_exists(SITE_PATH . '/images/' . $alias . '/config.yml')) { 
            $cli->error("Image already exists with the alias, $alias");
            return;
        }

        // Get account
        $acct = $this->acct_helper->get();

        // Build image
        list($dirs, $files) = $this->opus->build('install_image', SITE_PATH, [
            'alias' => $alias,
            'username' => $acct->getUsername()
        ]);

        // Success
        $cli->success("The installation image '$alias' has been successfully created, and can be found at:", $dirs);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Installation Image',
            usage: 'image create <ALIAS>',
            description: 'Creates a new installation image, which can be uploaded to the repository and later used for quick deployment of fully configured systems.'
        );

        $help->addParam('alias', 'The alias of the installation image to create.');
        $help->addExample('./apex image create ecommerce');

        // Return
        return $help;
    }

}





