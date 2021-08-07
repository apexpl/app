<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests;

use Apex\Svc\{Di, Db};
use PHPUnit\Framework\TestCase;

/**
 * Custom assertions
 */
class CustomAssertions extends TestCase
{

    /**
     * assertFileContains
     */
    final public function assertFileContains(string $text, string $filename):void
    {
        $this->checkFileContains($text, $filename, true);
    }

    final public function assertFileNotContains(string $text, string $filename):void
    {
        $this->checkFileContains($text, $filename, false);
    }

    private function checkFileContains(string $text, string $filename, bool $has = true):void
    { 

        // Check file exists
        if (!file_exists($filename)) { 
            $this->assertFileExists($filename);
            return;
        }
        $contents = file_get_contents($filename);

        // Check contains
        $method = $has === true ? 'assertStringContainsString' : 'assertStringNotContainsString';
        $this->$method($text, $contents);
    }

    /**
     * assertHasBvRow
     */
    final public function assertHasDbRow(string $sql, ... $args):void
    {
        $this->checkHasDbRow($sql, true, ...$args);
    }

    final public function assertNotHasDbRow(string $sql, ...$args):void
    {
        $this->checkHasDbRow($sql, false, ...$args);
    }

    private function checkHasDbRow(string $sql, bool $has = true, ...$args):void
    { 

        // Check db row
        $db = Di::get(Db::class);
        $row = $db->getRow($sql, ...$args);

        // Assert
        $method = $has === true ? 'assertNotNull' : 'assertNull';
        $this->$method($row, "Database row exists with SQL, $sql");
    }

}

