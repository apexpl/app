<?php
declare(strict_types = 1);

namespace Apex\Tests\App;

use Apex\App\Sys\Tests\Fixtures\PackageFixture;
use Apex\App\Sys\Tests\ApexTestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Cli - Create package
 */
class CliCreateTest extends ApexTestCase
{

    /**
     * Create package
     */
    public function testCreatePackage():void
    {
        $fixture = $this->cntr->make(PackageFixture::class);
        $fixture->create();
    }

    /**
     * Create form
     */
    public function testCreateForm():void
    {

        // Invalid package
        $res = $this->apex('create form junk-never-exists-package products');
        $this->assertStringContainsString('Package does not exist', $res);

        // Create
        $res = $this->apex('create form unit-test products');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Opus/Forms/Products.php');
        $this->assertFileContains('class Products', SITE_PATH . '/src/UnitTest/Opus/Forms/Products.php');

        // Create duplicate
        $res = $this->apex('create form unit-test products');
        $this->assertStringContainsString('already exists', $res);
    }

    /**
     * Create http controller
     */
    public function testCreateHttpController():void
    {

        // Invalid package
        $res = $this->apex('create http-controller junk-never-exists-package products');
        $this->assertStringContainsString('Package does not exist', $res);

        // Create
        $res = $this->apex('create http-controller unit-test test-pages --path testing/');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/HttpControllers/TestPages.php');
        $this->assertFileContains('class TestPages implements MiddlewareInterface', SITE_PATH . '/src/HttpControllers/TestPages.php');

        // Check routes.yml file
        $yaml = Yaml::parseFile(SITE_PATH . '/boot/routes.yml');
        $this->assertArrayHasKey('routes', $yaml);
        if (is_array($yaml['routes']['default'])) { 
            $this->assertArrayHasKey('testing/', $yaml['routes']['default']);
            $this->assertEquals('TestPages', $yaml['routes']['default']['testing/']);
        } else {
            $this->assertArrayHasKey('testing/', $yaml['routes']);
            $this->assertEquals('TestPages', $yaml['routes']['testing/']);
        }

        // Create duplicate
        $res = $this->apex('create http-controller unit-test test-pages');
        $this->assertStringContainsString('already exists', $res);
    }

    /**
     * Create listener
     */
    public function testCreateListener():void
    {

        // Invalid package
        $res = $this->apex('create listener junk-never-exists-package products');
        $this->assertStringContainsString('Package does not exist', $res);

        // Create
        $res = $this->apex('create listener unit-test tx');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Listeners/Tx.php');
        $this->assertFileContains('class Tx', SITE_PATH . '/src/UnitTest/Listeners/Tx.php');

        // Create duplicate
        $res = $this->apex('create listener unit-test tx');
        $this->assertStringContainsString('already exists', $res);
    }

    /**
     * Create table
     */
    public function testCreateTable():void
    {

        // Invalid package
        $res = $this->apex('create table junk-never-exists-package products');
        $this->assertStringContainsString('Package does not exist', $res);

        // Create
        $res = $this->apex('create table unit-test orders');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Opus/DataTables/Orders.php');
        $this->assertFileContains('class Orders', SITE_PATH . '/src/UnitTest/Opus/DataTables/Orders.php');

        // Create duplicate
        $res = $this->apex('create table unit-test orders');
        $this->assertStringContainsString('already exists', $res);
    }

    /**
     * Create view
     */
    public function testCreateView():void
    {

        // Invalid package
        $res = $this->apex('create view junk-never-exists-package products');
        $this->assertStringContainsString('Package does not exist', $res);

        // Create
        $res = $this->apex('create view unit-test public/test1234');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/views/html/public/test1234.html');
        $this->assertFileExists(SITE_PATH . '/views/php/public/test1234.php');
        $this->assertFileContains('class test1234', SITE_PATH . '/views/php/public/test1234.php');

        // Create duplicate
        $res = $this->apex('create view unit-test public/test1234');
        $this->assertStringContainsString('already exists', $res);
    }





}


