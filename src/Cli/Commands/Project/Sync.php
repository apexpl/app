<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\Svc\{App, Convert};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\PackagesStore;
use Apex\Armor\Auth\Operations\RandomString;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * SVN Sync
 */
class Sync implements CliCommandInterface
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Check for project
        if (!$pkg_alias = $this->redis->hget('config:project', 'pkg_alias')) {
            $cli->error("There is no project checked out on this system, hence SVN Sync can not be used.  Please see 'apex help project checkout' to checkout a project.");
            return;
        } elseif (!$pkg = $this->pkg_store->get('svn-sync')) {
            $cli->error("The 'svn-sync' package is not installed on this system.  To utilize this feature, please first install the package with './apex install svn-sync'");
            return;
        }
        $title_alias = $this->convert->case($pkg_alias, 'title');

        // Greeting
        $cli->sendHeader('SVN Sync Configuration');
        $cli->send("By enabling the SVN Sync feature, this system will be open to API calls so when successful commits with passed unit tests are performed against the project's repository, this system will be automatically updated with the commits and always in-sync with the repository.  Please use this feature with caution, as it will do a 'pull' for every successful commit to the repository.\r\n\r\n");

        // Check to enable
        $enabled = $cli->getConfirm('Enable SVN Sync on this system?') === true ? 1 : 0;
        $this->app->setConfigVar('svn-sync.enabled', $enabled);

        // Quit, if disabled
        if ($enabled == 0) { 
            $cli->send("Ok, SVN Sync feature disabled.  Goodbye.\r\n\r\n");
            return;
        }

        // Check API key
        $api_key = $this->app->config('svn-sync.apikey');
        if ($api_key !== null && strlen($api_key) > 0) {
            $cli->send("The following active API key was found for the SVN Sync feature:\r\n\r\n");
            $cli->send("    API Key: $api_key\r\n\r\n");

            // Ask to generate new key
            if (!$cli->getConfirm("Would you like to generate a new API key?")) {
                $cli->send("Ok, goodbye.\r\n\r\n");
                return;
            }
        }

        // Generate API key
        $api_key = RandomString::get(48);
        $this->app->setConfigVar('svn-sync.apikey', $api_key);

        // Send message
        $cli->sendHeader("SVN Sync API Key Generated");
        $cli->send("A new API key for the SVN Sync feature has been generated.  Within the /etc/$title_alias/package.yml file, please ensure to add the following:\r\n\r\n");
        $cli->send("    web_hooks:\r\n");
        $cli->send("      https://" . $this->app->config('core.domain_name') . "/svn_sync?apikey=$api_key\r\n\r\n");
        $cli->send("Once added, update the project within the repository with the command:\r\n\r\n");
        $cli->send("    ./apex package update $pkg_alias\r\n\r\n");
        $cli->send("Once done, all commits made to the project will be automatically synced with this system.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
        title: 'Activate SVN Sync',
            usage: 'project svn-sync',
            description: 'Allows you to activate / deactivate the SVN Sync feature, meaning all commits to the project repository will be automatically synced to this machine.'
        );
        $help->addExample('./apex project svn-sync');

        // Return
        return $help;
    }

}


