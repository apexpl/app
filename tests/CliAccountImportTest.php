<?php
declare(strict_types = 1);

use Apex\Svc\Db;
use App\Users\{User, UserProfiles};
use Apex\App\Sys\Tests\Fixtures\AccountFixture;
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * CLI - Account menu
 */
class CliAccountImportTest extends ApexTestCase
{

    /**
     * Import
     */
    public function testImport():void
    {

        // Initialize
        $profiles = $this->cntr->make(UserProfiles::class);

        // Delete user, if needed
        if ($user = User::loadUsername('testman')) { 
            $profiles->remove($user);
        }

        // Create user
        $user = $profiles->create([
            'username' => 'testman',
            'email' => 'testman@apexpl.io',
            'password' => 'password12345'
        ]);

        // Set inputs
        $inputs = [
            'testman',
            'password12345',
        ];

        $this->assertTrue(true);
    }

}


