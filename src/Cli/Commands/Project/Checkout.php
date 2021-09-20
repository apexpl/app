<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Stores\ReposStore;
use Apex\App\Sys\Utils\Io;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Checkout project
 */
class Checkout implements CliCommandInterface
{

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(Migration::class)]
    private Migration $migration;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $pkg_serial = $this->pkg_helper->getSerial(($args[0] ?? ''));
        $repo_alias = $opt['repo'] ?? 'apex';

        // Perform checks
        if ($project_alias = $redis->redis->hget('config:project', 'pkg_alias')) {
            $cli->error("The project $project_alias is already created on this system.  You can only work with one project at a time.");
        }

        // Get repo
        if (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository does not exist with alias, $repo_alias");
            return;
        }









