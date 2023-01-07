<?php
declare(strict_types = 1);

namespace Apex\App\Network\Models;

use Apex\App\Network\Models\LocalPackage;

/**
 * Merkle Root
 */
class MerkleTree
{

    /**
     * Constructor
     */
    public function __construct(
        private LocalPackage $pkg, 
        private string $merkle_root, 
        private ?string $prev_merkle_root,
        private array $files
    ) { 

    }

    /**
     * Get package
     */
    public function getPackage():LocalPackage
    {
        return $this->pkg;
    }

    /**
     * Get merkle root
     */
    public function getMerkleRoot():string
    {
        return $this->merkle_root;
    }

    /**
     * Get prev merkle root
     */
    public function getPrevMerkleRoot():string
    {
        return $this->prev_merkle_root;
    }

    /**
     * Get files
     */
    public function getFiles():array
    {
        return $this->files;
    }

    /**
     * toArray
     */
    public function toArray():array
    {

        // Set vars
        $vars = [
            'timestamp' => time(), 
            'package' => $this->pkg->getAlias(), 
            'author' => $this->pkg->getLocalUser(), 
            'merkle_root' => $this->merkle_root, 
            'prev_merkle_root' => $this->prev_merkle_root, 
            'files' => $this->files
        ];

        // Return
        return $vars;
    }

}


