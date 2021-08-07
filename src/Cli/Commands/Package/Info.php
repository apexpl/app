<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Info
 */
class Info implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        } elseif (null === ($repo = $pkg->getRepo())) { 
            $this->showUnpublished($cli, $pkg);
            return;
        }


        // Get info
        $res = $this->network->post($pkg->getRepo(), 'repos/check', ['pkg_serial' => $pkg->getSerial()]);
        print_r($res); exit;

        // Set variables
        $svn = $pkg->getSvnRepo();
        $repo_url = 'https://' . $repo->getHttpHost() . '/' . $pkg->getSerial();

        // Get current branch
        if (is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) { 
        $branch = $svn->getCurrentBranch() ?? 'trunk';
            $svn_dir = $branch == 'trunk' ? 'trunk' : 'branches/' . $branch;
        } else { 
            $branch = 'N/A'; 
            $svn_dir = 'trunk';
        }
        $svn_url = $svn->getSvnUrl($svn_dir);

        // Start package info
        $cli->send($pkg->getSerial() . ' Package Info');
        $cli->send("Alias:           " . $pkg->getAlias() . "\r\n");
        $cli->send("Name:            " . $res['name'] . "\r\n");
        $cli->send("Category:        " . $res['category'] . "\r\n");
        $cli->send("Author:          " . $pkg->getAuthor() . "\r\n");
        $cli->send("Repo URL:        " . $repo_url . "\r\n");
        $cli->send("SVN URL:         " . $svn_url . "\r\n\r\n");

        // Get price
        $price = $this->convert->money((float) $res['price']);
        if ($res['price_recurring'] > 0.00) { 
            $price .= ' + ' . $this->convert->money((float) $res['price_recurring']);
        }

        // Send additional
        $cli->send("Version:         " . $pkg->getVersion() . "\r\n");
        $cli->send("Current Branch:  " . $branch . "\r\n");
        $cli->send("Access:            " . ucwords($res['access']) . "\r\n");
        $cli->send("Price:         " . $price . "\r\n");

    }

    /**
     * Show unpublished info
     */
    private function showUnpublished(Cli $cli, LocalPackage $pkg):void
    {
        $cli->sendHeader($pkg->getAlias() . ' Package Info');
        $cli->send("Alias:      " . $pkg->getAlias() . "\r\n");
        $cli->send("Status:      Unpublished\r\n");
        $cli->send("Created:    " . $pkg->getInstalledAt()->format('M-d-Y H:i') . "\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

    }

}

