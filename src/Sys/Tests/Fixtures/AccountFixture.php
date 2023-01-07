<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests\Fixtures;

use Apex\Svc\{Db, App};
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\LocalAccount;
use Apex\App\Network\Stores\AccountsStore;
use App\Users\{User, UserProfiles};
use Apex\App\Sys\Tests\ApexTestCase;
use Symfony\Component\Process\Process;

/**
 * Account fixture
 */
class AccountFixture extends ApexTestCase
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(UserProfiles::class)]
    private UserProfiles $profiles;

    #[Inject(AccountsStore::class)]
    private AccountsStore $acct_store;

    /**
     * Register account
     */
    public function register(
        string $username = 'testman',
        string $email = 'testman@apexpl.io',
        string $password = 'acctpass123',
        string $dn_name = 'testman',
        bool $reset_confdir = true
    ) { 

        // Initialize
        $repo_alias = $this->app->config('enduro.repo_alias');
        $confdir = GetEnv('APEX_CONFDIR');

        // Reset confdir
        if ($reset_confdir === true) { 
            $this->io->removeDir($confdir);
        }

        // Delete test account, if needed
        if ($user = User::loadUsername($username)) { 
            $this->delete($username);
        }

        // Set input args
        $inputs = [
            $username,
            $email,
            $password,
            $password,
            'CA',
            'Ontario',
            'Toronto',
            'Apex Unit Testers',
            'Test Team',
            'utest@apexpl.io',
            true,
            $dn_name,
            'password12345',
            'password12345',
            $username,
            false,
            '',
            '',
            $username . '-ssh'
        ];

        // Create account
        $res = $this->apex('account register', $inputs, true);
        $this->assertStringContainsString('Please enter the username you wish to register with.', $res);
        $this->assertStringContainsString('Congratulations, your new account has been successfully registered', $res);
        $this->assertStringContainsString('For security and confidence, Apex requires all commits', $res);
        $this->assertStringContainsString('A new 4096 bit RSA key will now be generated', $res);
        // Check registration
        $this->checkRegister($username, $email);
    }

    /**
     * Check registration
     */
    public function checkRegister(string $username, string $email):void
    {

        // Initialize
        $repo_alias = $this->app->config('enduro.repo_alias');
        $confdir = GetEnv('APEX_CONFDIR');

        // Get acocunt from store
        $acct = $this->acct_store->get($username . '.' . $repo_alias);
        $this->assertNotNull($acct);
        $this->assertInstanceof(LocalAccount::class, $acct);
        $this->assertEquals($repo_alias, $acct->getRepoAlias());
        $this->assertEquals($username, $acct->getUsername());
        $this->assertEquals($email, $acct->getEmail());

        // Check config dir
        $filename = $username . '.' . $repo_alias . '.yml';
        $this->assertFileExists("$confdir/accounts/$filename");
        $this->assertFileContains($email, "$confdir/accounts/$filename");
        $this->assertFileExists("$confdir/keys/" . $acct->getSignKey() . ".pem");

        // Check certificate
        $crt_file = $confdir . '/certs/' . $username . '.' . $repo_alias . '.crt';
        $this->assertFileExists($crt_file);
        $details = openssl_x509_parse(file_get_contents($crt_file));
        $this->assertIsArray($details);
        $this->assertEquals($username . '@' . $repo_alias, $details['subject']['CN']);
        $this->assertEquals('ca@' . $repo_alias, $details['issuer']['CN']);

        // Check database
        $user = User::loadUsername($username);
        $this->assertNotNull($user);
        $this->assertInstanceof(User::class, $user);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($email, $user->getEmail());
    }

    /**
     * Delete
     */
    public function delete(string $username):void
    {

        // Get user
        if (!$user = User::loadUsername($username)) { 
            return;
        }

        // Delete
            $this->profiles->remove($user);
            $this->db->query("DELETE FROM ledger_certificates WHERE common_name = %s", $username . '@' . $this->app->config('enduro.repo_alias'));

        // Set args to delete SVN repos
        $args = [
            '/usr/bin/ssh',   
            $this->app->config('enduro.svn_ssh_alias'),  
            'rm',
            '-rf',
            '/svn/' . $username
        ];

        // Delete SVN repos
        $process = new Process($args);
        $process->run();
    }


}


