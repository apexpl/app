<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Opus;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\OpusHelper;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Pkg\Helpers\Registry;
use Apex\Opus\Opus;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Admin settings
 */
class AdminSettings implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(OpusHelper::class)]
    private OpusHelper $opus_helper;

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $path = $args[1] ?? '';

            // Get namespace
        $parts = explode('/', $path);
        $class_name = array_pop($parts);
        $namespace = $this->opus_helper->pathToNamespace(implode('/', $parts), 'Views');

        // Get package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        }

        // Get config
        $config = $pkg->getConfig();
        $config_vars = $config['config'] ?? [];
        if (count($config_vars) == 0) { 
            $cli->error("The package '$pkg_alias' does not contain any configuration variables, hence unable to geneate settings view.");
            return;
        }

        // Create form fields
        $form_fields = '';
        foreach ($config_vars as $key => $value) { 

            // Get field
            $field = match (true) { 
                is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0]) ? true : false => 'boolean',
                preg_match("/interval/", $key) ? true : false => 'date_interval',
                preg_match("/(amount|price|fee|cost)/", $key) ? true : false => 'amount',
                default => 'textbox'
            };

            // Add to fields
            $form_fields .= "        <s:ft_" . $field . " name=\"$key\" value=\"~config.$pkg_alias.$key~\" label=\"" . $this->convert->case($key, 'phrase') . "\">\n";
        }
        $php_vars = "'" . implode("',\n            '", array_keys($config_vars)) . "'";
        // Build view
        list($dirs, $files) = $this->opus->build('admin_settings', SITE_PATH, [
            'package' => $pkg_alias,
            'namespace' => $namespace,
            'class_name' => $class_name,
            'path' => $path,
            'php_vars' => $php_vars,
            'form_fields' => rtrim($form_fields)
        ]);

        // Add to registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $registry->add('views', $path);

        // Success
        $cli->success("Successfully generated new admin settings, which are now located at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate Admin Settings',
            usage: 'opus admin-settings <PKG_ALIAS> <VIEW_URI>',
            description: "Generates an admin view using configuration variables within designated package, and creates a form allowing user to update the settings."
        );

        // Add params
        $help->addParam('pkg_alias', 'The package alias settings view is being generated for.');
        $help->addParam('view_PATH', 'The path / filename of the new view to generate, relative to the /views/html/ directory.');
        $help->addExample('./apex opus admin-settings myshop admin/settings/myshop');

        // Return
        return $help;
    }

}


