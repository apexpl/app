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
class CliPackageLifecycleTest extends ApexTestCase
{

    /**
     * Initial commit
     */
    public function testInitialCommit()

