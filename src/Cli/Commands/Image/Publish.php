<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Image;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\AccountHelper;
use Apex\App\Network\Stores\{InstallationImagesStore, ReposStore};
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\NetworkClient;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;

/**
 * Publish installation image
 */
class Publish implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(accountHelper::class)]
    private AccountHelper $acct_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(InstallationImagesStore::class)]
    private InstallationImagesStore $image_store;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $repo_alias = $opt['repo'] ?? 'apex';

        // Check alias
        $alias = $this->convert->case(($args[0] ?? ''), 'lower');
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) { 
            $cli->error("Invalid image alias specified, $alias");
            return;
        } elseif (!file_exists(SITE_PATH . '/images/' . $alias . '/config.yml')) { 
            $cli->error("Installation image does not exist with alias, $alias");
            return;
        } elseif (!$image = $this->image_store->get($alias)) { 
            $cli->error("Installation image does not exist with alias, $alias");
            return;
        }

        // Get account
        if (!$acct = $this->acct_helper->get()) { 
            $cli->error("Unable to retrieve account.");
            return;
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("No repo exists on this machine with the alias, $repo_alias");
            return;
        }

        // Get readne fuke
        $readme = '';
        if (file_exists(SITE_PATH . '/images/' . $alias . '/Readme.md')) { 
            $readme = file_get_contents(SITE_PATH . '/images/' . $alias . '/Readme.md');
        }

        // Create zip archive
        $zip_file = $this->io->createZipArchive(SITE_PATH . '/images/' . $alias);
        $zip_contents = base64_encode(file_get_contents($zip_file));

        // Send http request
        $this->network->setAuth($acct);
        $res = $this->network->post($repo, 'images/publish', [
            'name' => $image->getName(),
            'version' => $image->getVersion(),
            'access' => $image->getAccess(),
            'description' => $image->getDescription(),
            'readme' => $readme,
            'contents' => $zip_contents
        ]);

        // Success
            $cli->sendHeader('Successfully Published Installation image');
        $cli->send("The installation image has been successfully published, and you may now use it during a new installation of Apex with the command:\r\n\r\n");
        $cli->send("    ./apex --image " . $image->getName() . "\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Publish Installation Image',
            usage: 'image publish <ALIAS>',
            description: 'Publish an installation image to the repository, making it available to other systems during installation.'
        );

        $help->addParam('alias', 'The alias of the installation image to publish.');
        $help->addExample('./apex image publish ecommerce');

        // Return
        return $help;
    }

}


