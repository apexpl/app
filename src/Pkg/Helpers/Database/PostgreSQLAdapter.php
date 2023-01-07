<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers\Database;

use Apex\Svc\Db;
use Apex\App\Network\Models\LocalPackage;
use App\Enduro\Exceptions\EnduroRemoteDatabaseException;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;
use redis;

/**
 * mySQL Adapter
 */
class PostgreSQLAdapter
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Trasnfer local to staging server
     */
    public function transferLocalToStage(LocalPackage $pkg, string $db_password, string $dbhost, int $dbport):void
    {

        $dbinfo = $this->redis->hgetall('config:db.master');
        $dbname = str_replace('-', '_', ($pkg->getAuthor() . '_' . $pkg->getAlias()));

        // Delete dump file, if exists
        $dump_file = SITE_PATH . '/transfer.dump';
        if (file_exists($dump_file)) {
            unlink($dump_file);
        }

        // Dump database
        $dsn_local = 'postgresql://' . $dbinfo['user'] . ':' . $dbinfo['password'] . '@' . $dbinfo['host'] . ':' . $dbinfo['port'] . '/' . $dbinfo['dbname'];
        shell_exec("pg_dump -Fc $dsn_local -f $dump_file");

                // Transfer to remote
        shell_exec("export PGPASSWORD=$db_password && pg_restore --no-owner --host=$dbhost --port=$dbport --username=$dbname --dbname=$dbname $dump_file");

        // Delete dump file
        unlink($dump_file);
        return;
    }

}



