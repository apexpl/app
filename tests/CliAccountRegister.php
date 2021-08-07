<?php
declare(strict_types = 1);

use Apex\Svc\Db;
use App\Users\{User, UserProfiles};
use Apex\App\Sys\Tests\Fixtures\AccountFixture;
use Apex\App\Sys\Tests\ApexTestCase;

/**
 * CLI - Account menu
 */
class CliAccountRegisterTest extends ApexTestCase
{

    /**
     * Register
     */
    public function testRegister()
    {
        $fixture = $this->cntr->make(AccountFixture::class);
        $fixture->register();
    }

    /**
     * Register with existing distinguished name
     */
    public function testRegisterWithExistingDistinguishedName():void
    {

        // Delete if exists
        if ($user = User::loadUsername('test-child')) { 
            $profiles = $this->cntr->make(UserProfiles::class);
        $profiles->remove($user);
        }

        // Set input args
        $inputs = [
            'test-child',
            'test-child@apexpl.io',
            'password12345',
            'password12345',
            'testman',
            '',
            'password12345',
            'password12345',
            'test-child',
            true
        ];

        // Create user
        $res = $this->apex('account register', $inputs);
        $this->assertStringContainsString('Signing Certificate', $res);
        $this->assertStringContainsString('The following distinguished names have been found:', $res);
        $this->assertStringContainsString('The following private keys have been found:', $res);
        $this->assertStringContainsString('Registration Successful', $res);

        // Check account
        $fixture = $this->cntr->make(AccountFixture::class);
        $fixture->checkRegister('test-child', 'test-child@apexpl.io');
    }

    /**
     * Register with existing distinguished name
     */
    public function testRegisterWithExistingDistinguishedNameAndRsaKey():void
    {

        // Delete if exists
        if ($user = User::loadUsername('test-with-key')) { 
            $profiles = $this->cntr->make(UserProfiles::class);
            $profiles->remove($user);
        }

        // Set input args
        $inputs = [
            'test-with-key',
            'test-with-key@apexpl.io',
            'password12345',
            'password12345',
            'testman',
            'testman',
            true
        ];

        // Create user
        $res = $this->apex('account register', $inputs);
        $this->assertStringContainsString('Signing Certificate', $res);
        $this->assertStringContainsString('The following distinguished names have been found:', $res);
        $this->assertStringContainsString('The following private keys have been found:', $res);
        $this->assertStringContainsString('Registration Successful', $res);

        // Check account
        $fixture = $this->cntr->make(AccountFixture::class);
        $fixture->checkRegister('test-with-key', 'test-with-key@apexpl.io');
    }

    /**
     * Register with existing rsa key
     */
    public function testRegisterWithExistingRsaKey():void
    {

        // Delete if exists
        if ($user = User::loadUsername('test-with-rsa')) { 
            $profiles = $this->cntr->make(UserProfiles::class);
            $profiles->remove($user);
        }

        // Set input args
        $inputs = [
            'test-with-rsa',
            'test-with-rsa@apexpl.io',
            'password12345',
            'password12345',
            '',
            'CA',
            'Alberta',
            'Edmonton',
            'Test With Key',
            'Dev Team',
            'test-with-rsa@apexpl.io',
            true,
            '',
            'testman',
            true
        ];

        // Create user
        $res = $this->apex('account register', $inputs);
        $this->assertStringContainsString('Signing Certificate', $res);
        $this->assertStringContainsString('The following distinguished names have been found:', $res);
        $this->assertStringContainsString('The following private keys have been found:', $res);
        $this->assertStringContainsString('Registration Successful', $res);

        // Check account
        $fixture = $this->cntr->make(AccountFixture::class);
        $fixture->checkRegister('test-with-rsa', 'test-with-rsa@apexpl.io');
    }

    /**
     * Test with separate ssh key
     */
    public function testRegisterWithSeparateSshKey():void
    {

        // Delete if exists
        if ($user = User::loadUsername('test-with-ssh')) { 
            $profiles = $this->cntr->make(UserProfiles::class);
            $profiles->remove($user);
        }

        // Set input args
        $inputs = [
            'test-with-ssh',
            'test-with-ssh@apexpl.io',
            'password12345',
            'password12345',
            'testman',
            '',
            'password12345',
            'password12345',
            'test-with-ssh',
            false,
            'sshpass12345',
            'sshpass12345',
            'test-with-ssh-key'
        ];

        // Create user
        $res = $this->apex('account register', $inputs);
        $this->assertStringContainsString('Signing Certificate', $res);
        $this->assertStringContainsString('The following distinguished names have been found:', $res);
        $this->assertStringContainsString('The following private keys have been found:', $res);
        $this->assertStringContainsString('Registration Successful', $res);

        // Check account
        $fixture = $this->cntr->make(AccountFixture::class);
        $fixture->checkRegister('test-with-ssh', 'test-with-ssh@apexpl.io');
    }

    /**
     * Clean up
     */
    public function testCleanup():void
    {

        // Initalize
        $profiles = $this->cntr->make(UserProfiles::class);
        $db = $this->cntr->get(Db::class);
        $usernames = ['testman', 'test-child', 'test-with-key', 'test-with-rsa', 'test-with-ssh'];

        // Go through users
        foreach ($usernames as $username) { 

            if (!$user = $profiles->loadUsername($username)) { 
                continue;
            }
            $profiles->remove($user);

            $common_name = $username . '@' . $this->app->config('enduro.repo_alias');
            $db->query("DELETE FROM ledger_certificates WHERE common_name = %s", $common_name);
            $this->assertNotHasDbRow("SELECT * FROM armor_users WHERE username = %s", $username);
        }

    }





}


