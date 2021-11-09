<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\Svc\Container;
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Models\{LocalPackage, MerkleTree};
use Apex\App\Attr\Inject;

/**
 * Merkel tree
 */
class MerkleTreeBuilder
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    /**
     * Build merkle root
     */
    public function build(LocalPackage $pkg, array $files, ?string $prev_merkle_root = null):MerkleTree
    {

        // initialize
        ksort($files);
        $hashes = array_values($files);

        // Create merkle root
        do { 

            $parents = [];
            while (count($hashes) > 0) { 
                $left = array_shift($hashes);
                $right = count($hashes) > 0 ? array_shift($hashes) : $left;
                $parents[] = hash('sha256', $left . $right);
            }

            // Check if done
            if (count($parents) == 1) {
                $merkle_root = $parents[0];
                break;
            }
            $hashes = $parents;

        } while (true);

        // Hash, if we have prev merkle root
        if ($prev_merkle_root !== null) { 
            $merkle_root = hash('sha256', $prev_merkle_root . $merkle_root);
        }

        // Instantiate merkle tree
        $tree = $this->cntr->make(MerkleTree::class, [
            'pkg' => $pkg, 
            'merkle_root' => $merkle_root, 
            'prev_merkle_root' => $prev_merkle_root, 
            'files' => $files
        ]);

        // Return
        return $tree;
    }

    /**
     * Build from package svn repo dir
     */
    public function buildPackage(LocalPackage $pkg, ?string $prev_merkle_root = null, string $rootdir = ''):MerkleTree
    {

        // Initialize
        if ($pkg->getType() == 'project') {
            $rootdir = SITE_PATH;
        } elseif ($rootdir == '') { 
            $rootdir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();
        }
        $filelist = $this->io->parseDir($rootdir);
        asort($filelist);

        // Create hashes
        $files = [];
        foreach ($filelist as $file) { 
            if (preg_match("/^(\.svn|\.apex|\.env|vendor)/", $file)) { 
                continue;
            } elseif (str_ends_with($file, '.pem')) {
                continue;
            }
            $files[$file] = sha1_file($rootdir . '/' . $file);
        }

        // Build merkel root
        $tree = $this->build($pkg, $files, $prev_merkle_root);
        return $tree;

    }



}



