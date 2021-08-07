<?php
declare(strict_types = 1);

namespace Apex\Tests\App;

use Apex\App\Pkg\PackageManager;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Tests\Fixtures\{AccountFixture, PackageFixture};
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * Cli - Create package
 */
class CliPackageTest extends ApexTestCase
{

    /**
     * Create package
     */
    public function testCreateErrors()
    {

        // Initialize
        $pkg_manager = $this->cntr->make(PackageManager::class);
        $store = $this->cntr->make(PackagesStore::class);

        // Create test user
        $acct_fixture = $this->cntr->make(AccountFixture::class);
        $acct_fixture->register();

        // Delete unit-test package, if exists
        if ($pkg = $store->get('unit-test')) { 
            $pkg_fixture = $this->cntr->make(PackageFixture::class);
            $pkg_fixture->delete('unit-test');
        }

        // Invalid alias
        $res = $this->apex('package create jd@jd!jdi');
        $this->assertStringContainsString("Invalid package alias", $res);

        // Package already exists
        $res = $this->apex('package create core');
        $this->assertStringContainsString("already exists", $res);

        // Invalid access
        $res = $this->apex('package create unit-test --access junk');
        $this->assertStringContainsString("Invalid access", $res);
    }

    /**
     * Create and delete
     */
    public function testCreateDelete():void
    {

        // Create
        $fixture = $this->cntr->make(PackageFixture::class);
        $fixture->create();

        // List packages
        $res = $this->apex('package ls');
        $this->assertStringContainsString('unit-test', $res);

        // Not exists error
        $res = $this->apex('package delete sdfsdfsfahkwe');
        $this->assertStringContainsString('Package does not exist', $res);

        // Not confirm
        $res = $this->apex('package delete unit-test', [false]);
        $this->assertStringContainsString('Ok, goodbye', $res);

        // Delete
        $fixture->delete('unit-test');
    }

}


