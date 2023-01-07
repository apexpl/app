<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

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

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get package
        if (!$info = $this->redis->hgetall('config:project')) {
            $cli->error("There is no project checked out on this system.");
            return;
        } elseif (!$pkg = $this->pkg_helper->get(($info['pkg_alias']))) { 
            return;
        }

        // Get info
        $repo = $pkg->getRepo();
        $res = $this->network->post($pkg->getRepo(), 'repos/check', ['pkg_serial' => $pkg->getSerial()]);
        $svn = $pkg->getSvnRepo();

        // Get current branch
        $branch = $svn->getCurrentBranch() ?? 'trunk';
        $svn_dir = $branch == 'trunk' ? 'trunk' : $branch;
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
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Project Info',
            usage: 'project info',
            description: 'View basic summary information regarding the checked out project on the system.'
        );
        $help->addExample('./apex project info');

        // Return
        return $help;
    }

}

