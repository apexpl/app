<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests\Fixtures;

use Apex\Svc\Convert;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Pkg\PackageManager;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Sys\Tests\Fixtures\AccountFixture;
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * Package fixture
 */
class PackageFixture extends ApexTestCase
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(AccountFixture::class)]
    private AccountFixture $acct_fixture;

    #[Inject(PackageManager::class)]
    private PackageManager $pkg_manager;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    /**
     * Create package
     */
    public function create(string $pkg_alias = 'unit-test', string $access = 'public'):void
    {

        // Delete package, if exists
        if ($pkg = $this->pkg_store->get($pkg_alias)) { 
            $remote = $pkg->getAuthor() == 'testman' ? true : false;
            $this->delete($pkg_alias);
        }

        // Create account
        $this->acct_fixture->register();

        // Create package
        $res = $this->apex("package create $pkg_alias --access $access");
        $this->assertStringContainsString("Successfully created", $res);

        // Ensure package exists
        $pkg = $this->pkg_store->get($pkg_alias);
        $this->assertNotNull($pkg);
        $this->assertInstanceOf(LocalPackage::class, $pkg);
        $this->assertEquals($pkg_alias, $pkg->getAlias());

    // Check files and directories
        $dir_alias = $this->convert->case($pkg_alias, 'title');
        foreach (['src', 'etc','docs','tests'] as $dir) { 
            $this->assertDirectoryExists(SITE_PATH . '/' . $dir . '/' . $dir_alias);
        }

        // Check files
        foreach (['package.yml', 'install.sql', 'remove.sql', 'migrate.php'] as $file) { 
            $this->assertFileExists(SITE_PATH . "/etc/$dir_alias/$file");
        }
    }

    /**
     * Delete
     */
    public function delete(string $pkg_alias, bool $remote = false):void
    {

        // Move /etc/ directory, if version controlled
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias . '/etc';
        if (is_dir($svn_dir)) { 
            $local_dir = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title');
            unlink($local_dir);
            rename($svn_dir, $local_dir);
        }

        // Delete
        $remote = $remote === true ? '--remote' : '';
        $res = $this->apex("package delete $pkg_alias $remote", [true]);
        $this->assertStringContainsString('Successfully deleted', $res);

        // Ensure gone from package store
        $pkg = $this->pkg_store->get($pkg_alias);
        $this->assertNull($pkg);

        // Ensure dirs are gone
        $dir_alias = $this->convert->case($pkg_alias, 'title');
        foreach (['src','etc','tests','docs'] as $dir) { 
            $this->assertDirectoryNotExists(SITE_PATH . "/$dir/$dir_alias");
        }

    }

    /**
     * Develop
     */
    public function develop(string $pkg_alias = 'unit-test'):void
    {

        // Create
        $this->create($pkg_alias);
        $dir_alias = $this->convert->case($pkg_alias, 'title');

        // Add some files
        $this->apex("opus model $dir_alias/Models/Admin --dbtable admin", [true]);
        $this->apex("opus collection $dir_alias/Models/AdminStore --item_class $dir_alias/Models/Admin");
        $this->apex("create view $pkg_alias public/test_page");
        $this->apex("create http-controller $pkg_alias testing");
    }

}

