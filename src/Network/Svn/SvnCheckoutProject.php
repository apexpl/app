<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\{Db, Container};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Sys\Utils\Io;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;
use redis;

/**
 * Svn Checkout Project
 */
class SvnCheckoutProject
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Migration::class)]
    private Migration $migration;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(LocalPackage $pkg, bool $has_staging, array $dbinfo = []):void
    {

        // Get tmp directory
        $tmp_dir = sys_get_temp_dir() . '/apex-' . uniqid();
        if (is_dir($tmp_dir)) { 
            $this->io->removeDir($tmp_dir);
        }

        // Checkout package
        $svn = $pkg->getSvnRepo();
        $svn->setTarget('trunk', 0, false, false);
        if (!$svn->exec(['checkout'], [$tmp_dir], true)) {
            $svn->setTarget('trunk');
            if (!$svn->exec(['checkout'], [$tmp_dir], true)) {
                $this->cli->error("Unable to checkout project, aborting.");
                return;
            }
        }

        // Create prev_fs directory
        $prev_dir = SITE_PATH . '/.apex/prev_fs';
        $this->io->createBlankDir($prev_dir);

        // Transfer site_path files to prev_fs directory
        $files = scandir(SITE_PATH);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..', '.apex', '.svn', '.env', 'vendor'])) { 
                continue;
            }
            $this->io->rename(SITE_PATH . '/' . $file, "$prev_dir/$file");
        }

        // Copy tmp_dir over to site_path
    $files = scandir($tmp_dir);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..', 'vendor'])) {
                continue;
            }
            $this->io->rename("$tmp_dir/$file", SITE_PATH . '/' . $file);
        }

        // Create /storage/ directory, if needed
        if (!is_dir(SITE_PATH . '/storage/logs')) {
            mkdir(SITE_PATH . '/storage/logs', 0755, true);
        }

        // Install migrations
        $this->installMigrations();

        // Get database adapter
        //$parts = explode("\\", $this->db::class);
        //$adapter_class = "Apex\\App\\Pkg\\Helpers\\Database\\" . array_pop($parts) . "Adapter";
        //$db_adapter = $this->cntr->make($adapter_class);
        //$db_adapter->transferStageToLocal($pkg, $dbinfo['password'], $dbinfo['host'], (int) $dbinfo['port']);

        // Reset redis
        $process = new Process(['./apex', 'sys', 'reset-redis', '--full']);
        $process->setWorkingDirectory(SITE_PATH);
        $process->run();

        // Update composer
        $process = new Process(['composer', 'update', '-n']);
        $process->setWorkingDirectory(SITE_PATH);
        $process->run();

        // Save to redis
        $dbinfo['pkg_alias'] = $pkg->getAlias();
        $dbinfo['has_staging'] = $has_staging === true ? 1 : 0;
        $this->redis->hmset('config:project', $dbinfo);
    }

    /**
     * Install migrations
     */
    private function installMigrations():void
    {

        // Drop all tables
        $this->db->dropAllTables();

        // Create migrations table
        $sql = trim(file_get_contents(SITE_PATH . '/vendor/apex/migrations/config/setup.sql'));
        $sql = str_replace('~table_name~', 'internal_migrations', $sql);
        $this->db->query($sql);
        $this->db->clearCache();

        // Go through packages
        $packages = $this->pkg_store->list();
        foreach ($packages as $pkg_alias => $vars) {
            $pkg = $this->pkg_store->get($pkg_alias);
            $this->migration->install($pkg);
        }

    }

}


