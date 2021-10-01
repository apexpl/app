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

        // Set variables
        $svn = $pkg->getSvnRepo();

        // Get current branch
        if (is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) { 
        $branch = $svn->getCurrentBranch() ?? 'trunk';
            $svn_dir = $branch == 'trunk' ? 'trunk' : $branch;
        } else { 
            $branch = 'N/A'; 
            $svn_dir = 'trunk';
        }
        $svn_url = $svn->getSvnUrl($svn_dir, true);

        // Get repo url
        $repo_url = 'https://' . $repo->getHttpHost() . '/' . $pkg->getSerial();
        if ($svn_dir != 'trunk') { 
            $repo_url .= '/' . $svn_dir;
        }

        // Get latest release
        if (!$latest_release = $svn->getLatestRelease()) { 
            $latest_release = 'Not Released';
        }

        // Get price
        $price = $this->convert->money((float) $res['price']);
        if ($res['price_recurring'] > 0.00) { 
            $price .= ' + ' . $this->convert->money((float) $res['price_recurring']);
        }

        // Set output vars
        $output = [
            'Name:' => $pkg->getSerial(),
            'Category:' => $res['category'],
            'Latest Release:' => $latest_release,
            'Current Branch:' => '/' . $svn_dir,
            '' => '',
            'Access' => ucwords($res['access']),
            'Price:' => $price,
            'Downloads:' => (string) $res['downloads'],
            'Stars:' => (string) $res['stars'],
            'Watchers:' => (string) $res['watchers'],
            '' => '',
            'Web:' => $repo_url,
            'SVN:' => $svn_url
        ];

        // Send output
        $cli->sendHeader($pkg->getSerial() . ' Package Info');
        $cli->sendArray($output);
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

        $help = new CliHelpScreen(
            title: 'Get Package Info',
            usage: 'package info <PKG_ALIAS>',
            description: 'View basic information on a locally installed package.'
        );

        $help->addParam('pkg_alias', 'The alias of the package to display info about.');
        $help->addExample('./apex package info my-shop');

        // Return
        return $help;
    }

}

