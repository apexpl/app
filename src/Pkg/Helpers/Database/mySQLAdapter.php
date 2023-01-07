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
class mySQLAdapter
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

        // Set cmd
        $cmd = "mysqldump -u$dbinfo[user] -p$dbinfo[password] -h$dbinfo[host] -P$dbinfo[port] $dbinfo[dbname] | sed -e 's|^/[*]!50001 CREATE ALGORITHM=UNDEFINED [*]/|/*!50001 CREATE */|' -e '/^[/][*]!50013 DEFINER=/d' | mysql -u$dbname -p$db_password -h$dbhost -P$dbport $dbname";
        shell_exec($cmd);
        return;

        // Set args to transfer sql database
        $args = [
            'mysqldump',
            '-u' . $dbinfo['user'],
            '-p' . $dbinfo['password'],
            '-h' . $dbinfo['host'],
            '-P' . $dbinfo['port'],
            $dbinfo['dbname'],
            '|',
            "sed -e 's|^/[*]!50001 CREATE ALGORITHM=UNDEFINED [*]/|/*!50001 CREATE */|' -e '/^[/][*]!50013 DEFINER=/d'",
            '|',
            'mysql',
            '-u' . $dbname,
            '-p' . $db_password,
            '-h' . $dbhost,
            '-P' . $dbport,
            $dbname
        ];

        // Run process
        $process = new Process($args);
        $process->run();

        // Check for error
        if ($process->isSuccessful() === true) {
            throw new EnduroRemoteDatabaseException("Unable to transfer database to staging environment, error: " . $process->getErrorOutput());
        }

    }

    /**
     * Transfer staging server to local database
     */
    public function transferStageToLocal(LocalPackage $pkg, string $db_password, string $dbhost, int $dbport):void
    {

        // Drop all database tables
        $this->db->dropAllTables();

        $dbinfo = $this->redis->hgetall('config:db.master');
        $dbname = str_replace('-', '_', ($pkg->getAuthor() . '_' . $pkg->getAlias()));

        // Set cmd
        $cmd = "mysqldump -u$dbname -p$db_password -h$dbhost -P$dbport $dbname | sed -e 's|^/[*]!50001 CREATE ALGORITHM=UNDEFINED [*]/|/*!50001 CREATE */|' -e '/^[/][*]!50013 DEFINER=/d' | mysql -u$dbinfo[user] -p$dbinfo[password] -h$dbinfo[host] -P$dbinfo[port] $dbinfo[dbname]";
        shell_exec($cmd);
        return;

    }

}



