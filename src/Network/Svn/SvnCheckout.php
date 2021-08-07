<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\Cli;
use Apex\App\Network\Svn\{SvnRepo, SvnInventory};
use Apex\App\Pkg\Filesystem\Package\{Compiler, Inventory};
use Apex\App\Network\Sign\MerkleTreeBuilder;

/**
 * SVN Checkout
 */
class SvnCheckout
{

    #[Inject(SvnInventory::class)]
    private SvnInventory $svn_inventory;

    #[Inject(Inventory::class)]
    private Inventory $pkg_inventory;

    #[Inject(MerkleTreeBuilder::class)]
    private MerkleTreeBuilder $tree_builder;

    #[Inject(Compiler::class)]
    private Compiler $compiler;

    #[Inject(Cli::class)]
    private Cli $cli;

    /**
     * Checkout
     */
    public function process(SvnRepo $svn, string $dir_name = 'trunk', int $rev_id = 0):bool
    {

        // Initialize
        $svn->setTarget($dir_name, $rev_id);
        $pkg = $svn->getPackage();
        $local_dir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();

        // Check local directory
        if (is_dir($local_dir)) { 
            $this->cli->error("Local SVN directory already exists for the package $pkg_alias, please update instead, see 'apex help package update' for details.");
            return false;
        } elseif (!is_dir(dirname($local_dir))) { 
            mkdir(dirname($local_dir), 0755, true);
        }

        // Get merkle root, and compare inventory if needed 
        if (!$handle_diff = $this->compareInventory($svn)) { 
            return false;
        }

        // Checkout package
        $svn->setTarget($dir_name, $rev_id);
        $this->cli->send("checking out /$dir_name from package " . $pkg->getAlias() . "... ");
        if (!$res = $svn->exec(['checkout'], [$local_dir])) { 
            $this->cli->error($svn->error_output);
            return false;
        }

        // Get number of files / dirs
        $num = substr_count($res, "\nA");
        $this->cli->send("done ($num files / directories).\r\n");

        // Compile package
        $this->compiler->compile($pkg, $handle_diff, true);
        return true;
    }

    /**
     * Compare inventory
     */
    private function compareInventory(SvnRepo $svn):string
    {

        // Initialize
        $pkg = $svn->getPackage();

        // Get merkle root of SVN repo
        if (!$merkle_root = $svn->getProperty('merkle_root')) { 
            return 'use_local';
        }

        // Get local package
        $inv = $this->pkg_inventory->get($pkg);
        $tree = $this->tree_builder->build($svn->getPackage(), $inv);
        $local_merkle_root = $tree->getMerkleRoot();

        // Check merkle roots
        if ($merkle_root == $local_merkle_root) { 
            return 'use_local';
        }

        // Compare
        $handle_diff = $this->svn_inventory->compare($svn->getPackage(), $tree->getFiles());
        if ($handle_diff == 'cancel') { 
            return null;
        }
        return $handle_diff;

    }

}






