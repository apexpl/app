<?php
declare(strict_types = 1);

namespace Apex\App\Pkg;

use Apex\Svc\{Convert, Container, Db};
use Apex\App\Cli\Cli;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\{LocalAccount, LocalPackage, LocalRepo};
use Apex\App\Network\NetworkClient;
use Apex\App\Network\Svn\SvnCommit;
use Apex\App\Pkg\Helpers\Database\{mySQLAdapter, PostgreSQLAdapter};
use Apex\Opus\Opus;
use Apex\App\Sys\Utils\Io;
use Apex\App\Attr\Inject;
use redis;

/**
 * Project manager
 */
class ProjectManager
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(Opus::class)]
    private Opus $opus;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(SvnCommit::class)]
    private SvnCommit $svn_commit;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Create
     */
    public function create(string $pkg_alias, LocalRepo $repo, LocalAccount $acct, string $author = '', bool $is_staging = false):LocalPackage
    {

        // Initialize
        $name = $this->convert->case($pkg_alias, 'phrase');

        // Build via Opus
        $this->opus->build('package', SITE_PATH, [
            'alias' => $pkg_alias,
            'type' => 'project',
            'access' => 'private',
            'name' => $name
        ]);

        // Create package instance
        $pkg = $this->cntr->make(LocalPackage::class, [
            'is_local' => true,
            'type' => 'project',
            'alias' => $pkg_alias,
            'author' => $author,
            'local_user' => $acct->getUsername(),
            'repo_alias' => $repo->getAlias()
        ]);

        // Create new repository
        $this->network->setAuth($acct);
        $res = $this->network->post($pkg->getRepo(), 'repos/create', $pkg->getJsonRequest());

        // Save package
        $this->pkg_store->save($pkg);

        // Checkout repository
        $svn = $pkg->getSvnRepo();
        $tmp_dir = sys_get_temp_dir() . '/apex-' . uniqid();
        $svn_url = $svn->getSvnUrl('trunk', false);
        $svn->exec(['checkout'], [$tmp_dir]); 

        // Rename local SVN directory
        $this->io->rename($tmp_dir . '/.svn', SITE_PATH . '/.svn');
        $this->io->removeDir($tmp_dir);

        // Save .svnignore file
        file_put_contents(SITE_PATH . '/.svnignore', "vendor\n.apex\n.env\nstorage\n");
        $svn->setTarget('trunk', 0, true, false, SITE_PATH);

        // Add necessary files / dirs
        $files = scandir(SITE_PATH);
        foreach ($files as $file) { 
            if (in_array($file, ['.', '..', '.env', '.svnignore'])) { 
                continue;
            } elseif (preg_match("/^(\.svn|\.apex|vendor)/", $file)) { 
                continue;
            }
            $svn->add($file);
        }

        // Initial commit
        $this->cli->send("\r\n");
        $this->cli->send("Performing initial commit, please be patient this may take a few minutes... \r\n");
        $this->svn_commit->process($pkg, ['-m', 'Initial commit']);

        // Create staging environment, if needed
        if ($is_staging === true) {
            $this->createStagingEnvironment($pkg);
        }

        // Return
        return $pkg;
    }

    /**
     * Create staging environment
     */
    public function createStagingEnvironment(LocalPackage $pkg):array
    {

        // Send message
        $this->cli->send("Creating new staging environment, please be patient as this may take a few minutes...\r\n");

        // Get database driver
        $parts = explode("\\", $this->db::class);
        $db_driver = strtolower(array_pop($parts));

        // Send api call
        $this->network->resetAuth();
        $res = $this->network->post($pkg->getRepo(), 'stage/create', [
            'pkg_serial' => $pkg->getSerial(),
            'db_driver' => $db_driver
        ]);

        // Load db adapter
        if ($db_driver == 'postgresql') { 
            $dbadapter = $this->cntr->make(PostgreSQLAdapter::class);
        } else { 
            $dbadapter = $this->cntr->make(mySQLAdapter::class);
        }

        // Transfer database to staging environment
        $dbadapter->transferLocalToStage($pkg, $res['db_password'], $res['db_host'], (int) $res['db_port']);

        // Finalize
        $this->network->resetAuth();
        $this->network->post($pkg->getRepo(), 'stage/finalize', [
            'pkg_serial' => $pkg->getSerial()
        ]);

        // Set result
        $dbname = str_replace('-', '_', ($pkg->getAuthor() . '_' . $pkg->getAlias()));
        $res = [
            'dbname' => $dbname,
            'user' => $dbname,
            'password' => $res['db_password'],
            'host' => $res['db_host'],
            'port' => $res['db_port']
        ];

        // Return
        return $res;
    }

    /**
     * Delete
     */
    public function delete(LocalPackage $pkg):void
    {

        // Wipe from redis
        $this->redis->del('config:project');

        // Remove .svn directory
        $this->io->removeDir(SITE_PATH . '/.svn');
    }

}

