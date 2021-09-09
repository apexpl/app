<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\{CliCommandInterface, TabPageInterface};
use redis;

/**
 * Create tab page
 */
class TabPage implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

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

        // Initialize
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        }
        $pkg_alias = $pkg->getAlias();
        $parent = $args[1] ?? '';
        $alias = $this->convert->case(($args[2] ?? ''), 'title');

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (!preg_match("/^([a-zA-Z0-9_-]+)\.([a-zA-Z0-9_-]+)$/", $parent, $match)) { 
            $cli->error("Invalid parent tab control specified, $parent.  Must be formatted as PACKAGE.ALIAS");
            return;
        }

        // Ensure parent tab control exists
        $parent_class = "\\App\\" . $this->convert->case($match[1], 'title') . "\\Opus\\TabControls\\" . $this->convert->case($match[2], 'title');
        if (!class_exists($parent_class)) { 
            $cli->error("The parent tab control does not exist at, $parent ($parent_class)");
            return;
        }

        // Get filename of new tab page
        $child_dir = $this->convert->case($match[1], 'title') . '_' . $this->convert->case($match[2], 'title');
        $filename = SITE_PATH . '/src/' . $this->convert->case($pkg_alias, 'title') . '/Opus/TabControls/' . $child_dir . '/' . $this->convert->case($alias, 'title') . '.php'; 

        // Check if tab page already exists
        if (file_exists($filename)) { 
            $cli->error("The tab page '$alias' already exists within the tab control '$parent'");
            return;
        }

        // Build
        list($dirs, $files) = $this->opus->build('tab_page', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias,
            'parent_package' => $match[1],
            'parent_alias' => $match[2]
        ]);

        // Add to redis
        $class_name = "App\\" . $this->convert->case($pkg_alias, 'title') . "\\Opus\\TabControls\\$child_dir\\" . $this->convert->case($alias, 'title');
        $this->redis->sadd('config:child_casses:' . $parent_class, $class_name);
        $this->redis->sadd('config:interface:' . TabPageInterface::class, $class_name);

        // Success message
        $cli->success("Successfully created new tab page which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Create Tab Page',
            usage: 'create tab-page <PKG_ALIAS> <PARENT> <ALIAS>',
            description: 'Create new tab page component.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create tab page within.');
        $help->addParam('parent', "The parent tab control to add tab page to, must be formatted as PACKAGE.ALIAS.");
        $help->addParam('alias', 'The alias / filename of the tab page to create.');
        $help->addExample('./apex create tab-page myshop users.manage invoices');

        // Return
        return $help;
    }

}


