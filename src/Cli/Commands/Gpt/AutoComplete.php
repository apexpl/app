<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\{PackageHelper, OpusHelper};
use Apex\App\Pkg\Gpt\{GptAutoComplete, GptClient};
use Apex\App\Interfaces\Opus\{CliCommandInterface, AutoCompleteInterface};
use Apex\App\Attr\Inject;
use redis;

/**
 * Create auto complete
 */
class AutoComplete implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(GptClient::class)]
    private GptClient $gpt_client;

    #[Inject(GptAutoComplete::class)]
    private GptAutoComplete $gpt_auto_complete;

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
        $alias = $this->convert->case(($args[1] ?? ''), 'title');

        // Get dbtable
        $opt = $cli->getArgs(['dbtable']);
        $dbtable = $opt['dbtable'] ?? '';
        if ($dbtable == '') {
            $cli->error("You did not specify a --dbtable flag, which is required for AI assisted generation.");
            return;
        }

        // Check
        if ($alias == '' || !preg_match("/^[a-zA-z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid alias specified, $alias");
            return;
        } elseif (file_exists(SITE_PATH . "/src/" . $this->convert->case($pkg_alias, 'title') . "/Opus/AutoCompletes/$alias.php")) { 
            $cli->error("The auto complete already exists with alias, $alias within the package $pkg_alias");
            return;
        }

        // Get item description
        list($item_desc, $sort_by) = $this->gpt_client->getItemDescription($pkg_alias, $dbtable);

// Generate class
        $filename = $this->gpt_auto_complete->generate($pkg_alias, $dbtable, $item_desc, $sort_by);
        $files = [$filename];

        // Success message
        $cli->success("Successfully created new auto-complete list which is now available at:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Generate Auto-Complete List',
            usage: 'gpt auto-complete <PKG_ALIAS> <ALIAS> --dbtable [DBTABLE]',
            description: 'Generate new auto-complete list component with AI assistance.'
        );

        // Params
        $help->addParam('pkg_alias', 'Package alias to create auto-complete list within.');
        $help->addParam('alias', 'The alias / filename of the auto-complete list to create.');
        $self->addFlag('--dbtable', 'Name of the database table auto-complete will search within.');

        $help->addExample('./apex gpt auto-complete my-shop invoices --dbtable shop_invoices');

        // Return
        return $help;
    }

}


