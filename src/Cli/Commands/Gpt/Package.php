<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Commands\Package\Create;
use Apex\App\Pkg\Gpt\{GptClient, GptSqlSchema, GptHashes, GptAdminMenus, GptView};
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Generate GPT
 */
class Package extends GptClient implements CliCommandInterface
{

    #[Inject(Create::class)]
    private Create $create_pkg;

    #[Inject(GptSqlSchema::class)]
    private GptSqlSchema $gpt_sql;

    #[Inject(GptHashes::class)]
    private GptHashes $gpt_hashes;

    #[Inject(GptAdminMenus::class)]
    private GptAdminMenus $gpt_admin_menus;

    #[Inject(GptView::class)]
    private GptView $gpt_view;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $pkg_alias = $args[0] ?? '';
        if ($pkg_alias == '') {
            $cli->error("You did not specify a package alias to generate code for.");
            return;
        }
        $pkg_alias = $this->convert->case($pkg_alias, 'lower');

        // Create package, if not exists
        if (!$pkg = $this->pkg_store->get($pkg_alias)) {

            // Create package
            $this->create_pkg->process($cli, [$pkg_alias]);
            if (!$pkg = $this->pkg_store->get($pkg_alias)) {
                return;
            }

            // Get description / prompt
            $cli->sendHeader("GPT Code Generation");
            $prompt = $this->getPrompt();

            // Save GPT description to package
            $pkg->setGptDescription($prompt);
            $this->pkg_store->save($pkg);
        }

        // Start chat
        $chat = $this->initChat($pkg_alias);

        // Generate SQL schema
        if (!$tables = $this->gpt_sql->generate($pkg_alias, $chat)) {
            return;
        }

        // Generate hashes
        $hashes = $this->gpt_hashes->generate($pkg_alias, array_keys($tables));

        // Generate menus
        $files = $this->gpt_admin_menus->generate($pkg_alias, $tables, $hashes);

        // Generate views
        $views = $this->gpt_view->generate($pkg_alias);
        array_push($files, ...$views);

        // Success message
        $cli->send("\n");
        $cli->sendHeader("Code Generation Complete");
        $cli->send("Successfully completed code generation with Chat GPT assistance.  All files that have been generated are listed within the generated_files.txt file.\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Define help scren
        $help = new CliHelpScreen(
            title: 'Generate Package and All Components',
            usage: 'gpt package <PKG_ALIAS>',
            description: 'Create new package and automatically generate all components for any functionality, including SQL schema, models, views, controllers, menus, et al.'
        );

        // Params
        $help->addParam('PKG_ALIAS', "Alias of the package to generate code for.");
        $help->addExample("./apex gpt package my-shop");

    // Return
        return $help;
    }

}





