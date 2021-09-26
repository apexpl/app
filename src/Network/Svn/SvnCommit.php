<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\Cli;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Svn\{SvnRepo, SvnCheckout};
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Network\NetworkClient;
use Apex\App\Pkg\Filesystem\Package\Compiler;
use Apex\App\Pkg\Helpers\RegistryCleaner;
use Apex\App\Network\Sign\Signer;
use Apex\App\Exceptions\{ApexCompilerException, ApexSvnRepoException};

/**
 * Commit
 */
class SvnCommit
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(RegistryCleaner::class)]
    private RegistryCleaner $cleaner;

    #[Inject(Compiler::class)]
    private Compiler $compiler;

    #[Inject(Signer::class)]
    private Signer $signer;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(NetworkClient::class)]
    private NetworkClient $network;

    /**
     * Process
     */
    public function process(LocalPackage $pkg, array $commit_args = []):void
    {

        // Initialize
        $svn = $pkg->getSvnRepo();

        // Check repo exists, if needed
        if ($pkg->getType() != 'project' && !is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) {
            if (!$this->checkRepoExists($svn)) { 
                return;
            }
        }

        // Compare merkle roots
        if (!$this->compareMerkleRoots($svn)) { 
            $this->cli->error("Your local working copy is older than the SVN repository.  Please first update, see 'apex help package pulle' for details.");
            return;
        }

        // Clean registry
        $this->cleaner->clean($pkg);

        // Compile the package
        $this->cli->send("Compiling package... ");
        $this->compiler->compile($pkg);
        $this->addUnwatchedFiles($svn);
        $this->cli->send("done.\r\n");

        // Sign package
        $this->signer->signPackage($svn);

        // Prepare for commit
        $svn->setTarget('', 0, true);
        $svn->checkSshAgent();

        // Process commit
        $svn->exec(['commit'], $commit_args, true);
    }

    /**
     * Check repo exists
     */
    private function checkRepoExists(SvnRepo $svn):bool
    {

        // Initialize
        $svn->setTarget('trunk');
        $pkg = $svn->getPackage();
        $this->cli->send("Checking repository exists... ");

        // Get svn info
        if (($info = $svn->info()) !== null) { 
            $this->cli->send("done.\r\n");
            return true;
        }
        $this->cli->send("not found.\r\nCreating new repository... ");

        // Create new repository
        $this->network->setAuth($pkg->getLocalAccount());
        $res = $this->network->post($pkg->getRepo(), 'repos/create', $pkg->getJsonRequest());

        // Success message.
        $this->cli->send("Successfully created new repository, which may be found at:\r\n\r\n");
        $this->cli->send("    Web: https://" . $pkg->getRepo()->getHttpHost() . "/" . $pkg->getAuthor() . "/" . $pkg->getAlias() . "/\r\n");
        $this->cli->send("    SVN: " . $svn->getSvnUrl() . "\r\n\r\n");

        // Return
        return true;
    }

    /**
     * Compare merkle roots
     */
    private function compareMerkleRoots(SvnRepo $svn):bool
    {

        // Check for project
        if ($svn->getPackage()->getType() == 'project') {
            return true;
        }

        // Get current branch
        if (is_dir(SITE_PATH . '/.apex/svn/' . $svn->getPackage()->getAlias())) { 
            $dir_name = $svn->getCurrentBranch();
        } else { 
            $dir_name = 'trunk';
        }

        // Initialize
        $svn->setTarget($dir_name);
        $this->cli->send("Comparing merkle roots... ");

        // Get remote svn merkle root
        $merkle_root = $svn->getProperty('merkle_root');
        if ($merkle_root === null) {
            $this->cli->send("detected first commit.\r\n");

            // Checkout, if needed
            if (!is_dir(SITE_PATH . '/.apex/svn/' . $svn->getPackage()->getAlias())) {
                return $svn->checkout(); 
            }
            return true;
        }

        // Get local merkle root
        $svn->setTarget('trunk', 0, true);
        $local_merkle_root = $svn->getProperty('merkle_root');
        if ($local_merkle_root != $merkle_root) {
            $this->cli->send("do not match.\r\n\r\n");
            return false;
    }

        // Return
        $this->cli->send("done.\r\n");
        return true;
    }

    /**
     * Add unwatched files
     */
    private function addUnwatchedFiles(SvnRepo $svn):void
    {

        // Get status
        $svn->setTarget('trunk', 0, true);
        $lines = explode("\n", $svn->exec(['status']));

        // Go through lines
        foreach ($lines as $line) { 

            if (!preg_match("/^(.)\s+(.+)$/", $line, $m)) { 
                continue;
            }
            $file = trim($m[2]);

            // Skip, if needed
            if (preg_match("/^(\.apex|vendor|\.env)/", $file)) {
                continue;
            }

            if ($m[1] == '?') { 
                $svn->add($file);
            } elseif ($m[1] == '!') { 
                $svn->rm($file);
            }
        }

    }

}

