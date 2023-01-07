<?php

namespace Apex\Tests\App;

use Apex\App\Pkg\Package;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Tests\Fixtures\AccountFixture;
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * Cli - Create package
 */
class CliPackageHelpTest extends ApexTestCase
{


    /**
     * Create help
     */
    public function test_create_help()
    {
        $res = $this->apex('help package create');
        $this->assertStringContainsString('creates a new package on the local machine', $res);
        $this->assertStringContainsString('--access', $res);
    }

}


