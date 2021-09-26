<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\{Db, Container};
use Apex\App\Cli\Cli;
use Apex\App\Cli\Commands\Sys\ResetRedis;
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Utils\Io;
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

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(ResetRedis::class)]
    private ResetRedis $reset_redis;

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
        $svn->setTarget('trunk');
        $svn->exec(['checkout'], [$tmp_dir], true);

        // Create prev_fs directory
        $prev_dir = SITE_PATH . '/.apex/prev_fs';
        $this->io->createBlankDir($prev_dir);

        // Transfer site_path files to prev_fs directory
        $files = scandir(SITE_PATH);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..', '.apex', '.svn'])) { 
                continue;
            }
            rename(SITE_PATH . '/' . $file, "$prev_dir/$file");
        }

        // Copy tmp_dir over to site_path
    $files = scandir($tmp_dir);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            rename("$tmp_dir/$file", SITE_PATH . '/' . $file);
        }

        // Get database adapter
        $parts = explode("\\", $this->db::class);
        $adapter_class = "Apex\\App\\Pkg\\Helpers\\Database\\" . array_pop($parts) . "Adapter";
        $db_adapter = $this->cntr->make($adapter_class);

        // Transfer database
        $db_adapter->transferStageToLocal($pkg, $dbinfo['password'], $dbinfo['host'], (int) $dbinfo['port']);

        // Reset redis
        $this->reset_redis->process($this->cli, []);

        // Save to redis
        $dbinfo['pkg_alias'] = $pkg->getAlias();
        $dbinfo['has_staging'] = $has_staging === true ? 1 : 0;
        $this->redis->hmset('config:project', $dbinfo);
    }

}


