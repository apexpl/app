<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Create test
 */
class Test implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        $alias = $this->convert->case(($args[1] ?? ''), 'title');

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/tests/" . $this->convert->case($pkg_alias, 'title') . "/$alias.php")) { 
            $cli->error("The test already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('test', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Success message
        $cli->success("Successfully created new test which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Test',
            usage: 'create test <PKG_ALIAS> <ALIAS>',
            description: 'Create new unit test.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create test within.');
        $help->addParam('alias', 'The alias / filename of the test to create.');
        $help->addExample('./apex create test my-shop invoices');

        // Return
        return $help;
    }

}


