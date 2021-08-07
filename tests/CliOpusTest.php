<?php
declare(strict_types = 1);

use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Tests\Fixtures\PackageFixture;
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * CLI - Commit Package
 */
class CliOpusTest extends ApexTestCase
{

    /**
     * Model - No Magic
     */
    public function testCreatePackage():void
    {

        $fixture = $this->cntr->make(PackageFixture::class);
        $fixture->create();
    }

    /**
     * Create model
     */
    public function testCreateModel():void
    {

        // Without magic
        $res = $this->apex("opus model UnitTest/Models/NoMagic --dbtable admin", [true]);
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Models/NoMagic.php');
        $this->assertFileContains('class NoMagic extends BaseModel', SITE_PATH . '/src/UnitTest/Models/NoMagic.php');

        // With magic
        $res = $this->apex("opus model UnitTest/Models/Magic --dbtable admin --magic", [true]);
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Models/Magic.php');
        $this->assertFileContains('class Magic extends MagicModel', SITE_PATH . '/src/UnitTest/Models/Magic.php');

        // Eloquent
        $res = $this->apex("opus model UnitTest/Models/Eloquent --type eloquent", [true]);
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/Models/Eloquent.php');
        $this->assertFileContains('class Eloquent extends Model', SITE_PATH . '/src/UnitTest/Models/Eloquent.php');
    }

    /**
     * Collection
     */
    public function testCreateCollection():void
    {
        $res = $this->apex('opus collection UnitTest/TestCollection --item_class UnitTest/Models/NoMagic');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/TestCollection.php');
        $this->assertFileContains('class TestCollection extends BaseCollection', SITE_PATH . '/src/UnitTest/TestCollection.php');
    }

    /**
     * Controller
     */
    public function testCreateController():void
    {
        $res = $this->apex('opus controller UnitTest/TestController --item_class UnitTest/Models/NoMagic');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/TestController.php');
        $this->assertFileContains('NoMagic::class', SITE_PATH . '/src/UnitTest/TestController.php');
    }

    /**
     * Iterator
     */
    public function testCreateIterator():void
    {
        $res = $this->apex('opus iterator UnitTest/TestIterator --item_class UnitTest/Models/NoMagic');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/TestIterator.php');
        $this->assertFileContains('class TestIterator extends BaseIterator', SITE_PATH . '/src/UnitTest/TestIterator.php');
    }

    /**
     * Stack
     */
    public function testCreateStack():void
    {
        $res = $this->apex('opus stack UnitTest/TestStack --item_class UnitTest/Models/NoMagic');
        $this->assertStringContainsString('Successfully created', $res);
        $this->assertFileExists(SITE_PATH . '/src/UnitTest/TestStack.php');
        $this->assertFileContains('class TestStack', SITE_PATH . '/src/UnitTest/TestStack.php');
    }


}


