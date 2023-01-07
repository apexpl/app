<?php
declare(strict_types = 1);

use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Utils\Io;
use Apex\App\Sys\Tests\Fixtures\PackageFixture;
use Apex\App\Sys\Tests\ApexTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * CLI - Commit Package
 */
class CliPackageCommitTest extends ApexTestCase
{

    /**
     * Initial commit
     */
    public function testInitialCommit()
    {

        // Create package
        $fixture = $this->cntr->make(PackageFixture::class);
        $fixture->develop();

        // Commit
        $res = $this->apex('package commit unit-test', ['password12345']);
        $this->assertStringContainsString('Creating new repository', $res);
        $this->assertStringContainsString('detected first commit', $res);
        $this->assertStringContainsString('Successfully completed commit', $res);

    }

    /**
     * Test add
     */
    public function testAdd():void
    {

        file_put_contents(SITE_PATH . '/unit_test.txt', 'unit test');
        $res = $this->apex('package add unit-test unit_test.txt');
        $this->assertStringContainsString('Added unit_test.txt', $res);

        // Check registry
        $yaml = Yaml::parseFile(SITE_PATH . '/etc/UnitTest/registry.yml');
        $ext_files = $yaml['ext_files'] ?? [];
        $this->assertContains('unit_test.txt', $ext_files);

        // Check symlink
        $this->assertTrue(is_link(SITE_PATH . '/unit_test.txt'));
        $this->assertFileExists(SITE_PATH . '/.apex/svn/unit-test/ext/unit_test.txt');
    }

    /**
     * Add directory
     */
    public function testAddDirectory()
    {

        // Copy directory
        $io = $this->cntr->make(Io::class);
        $io->removeDir(SITE_PATH . '/boot_test');
        system("cp -R " . SITE_PATH . "/boot " . SITE_PATH . "/boot_test");

        // Add directory
        $res = $this->apex('package add unit-test boot_test');
        $this->assertStringContainsString('Added boot_test', $res);

        // Check registry
        $yaml = Yaml::parseFile(SITE_PATH . '/etc/UnitTest/registry.yml');
        $ext_files = $yaml['ext_files'] ?? [];
        $this->assertContains('boot_test', $ext_files);

        // Check symlink
        $this->assertTrue(is_link(SITE_PATH . '/boot_test'));
        $this->assertFileExists(SITE_PATH . '/.apex/svn/unit-test/ext/boot_test/routes.yml');
    }

    /**
     * Commit update
     */
    public function testCommitUpdate():void
    {

        // Send commit
        $res = $this->apex('package commit unit-test', ['password12345']);
        $this->assertStringContainsString('Successfully completed commit', $res);

        // Get svn repo
        $pkg_store = $this->cntr->make(PackagesStore::class);
        $pkg = $pkg_store->get('unit-test');
        $svn = $pkg->getSvnRepo();

        // List /ext/ directory
        $svn->setTarget('trunk/ext');
        $files = array_map(fn ($file) => trim($file), explode("\n", $svn->exec(['list'])));
        $this->assertContains('boot_test/', $files);
        $this->assertContains('unit_test.txt', $files);
    }

    /**
     * Remove file
     */
    public function testRm():void
    {

        // Send call
        $res = $this->apex('package rm unit-test unit_test.txt');
        $this->assertStringContainsString('Removed unit_test', $res);

        // Check registry
        $yaml = Yaml::parseFile(SITE_PATH . '/etc/UnitTest/registry.yml');
        $ext_files = $yaml['ext_files'] ?? [];
        $this->assertNotContains('unit_test.txt', $ext_files);

        // Check symlink
        $this->assertFalse(is_link(SITE_PATH . '/unit_test.txt'));
        $this->assertFileDoesNotExists(SITE_PATH . '/.apex/svn/unit-test/ext/unit_test.txt');
    }

    /**
     * Commit update #2
     */
    public function testCommitUpdate2():void
    {

        // Send commit
        $res = $this->apex('package commit unit-test', ['password12345']);
        $this->assertStringContainsString('Successfully completed commit', $res);

        // Get svn repo
        $pkg_store = $this->cntr->make(PackagesStore::class);
        $pkg = $pkg_store->get('unit-test');
        $svn = $pkg->getSvnRepo();

        // List /ext/ directory
        $svn->setTarget('trunk/ext');
        $files = array_map(fn ($file) => trim($file), explode("\n", $svn->exec(['list'])));
        $this->assertContains('boot_test/', $files);
        $this->assertNotContains('unit_test.txt', $files);
    }

}


