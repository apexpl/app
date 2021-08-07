<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Create form
 */
class Form implements CliCommandInterface
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
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/Forms/$alias.php")) { 
            $cli->error("The form already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('form', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Success message
        $cli->success("Successfully created new form which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Form',
            usage: 'create form <PKG_ALIAS> <ALIAS>',
            description: 'Create a new form component.'
        );

        // Params
        $help->addParam('pkg_alias', 'The alias of the package to create component within.');
        $help->addParam('alias', 'The alias / filename of the component to create');
        $help->addExample('./apex create form my-shop products');

        // Return
        return $help;
    }

}


