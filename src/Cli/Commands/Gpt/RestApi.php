<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Gpt;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Pkg\Gpt\{GptClient, GptRestApi};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Gpt - Rest API
 */
class RestApi extends GptClient implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(GptRestApi::class)]
    private GptRestApi $gpt_rest_api;

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

        // Get prompt
        $prompt = $this->getPrompt("In plain text describe the REST API endpoint(s) you would like generated for the '$pkg_alias' package.");

        // Generate
        $files = $this->gpt_rest_api->generate($pkg_alias, $prompt);

        // Success
        $cli->success("Successfully generated the necessary REST API endpoints.  The following new files have been created:", $files);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Start help
        $help = new CliHelpScreen(
            title: 'Generate Rest API Endpoints',
        usage: 'gpt rest-api <PKG_ALIAS>',
        description: 'Use AI assisted development to automatically generate REST API end points.'
        );

        // Params
        $help->addParam('pkg_alias', "Alias of the package to generate API endpoints for.");
        $help->addExample('apex gpt rest-api my-shop');

        // Return
        return $help;
    }

}


