<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\App\Cli\CLi;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Exceptions\ApexSvnRepoException;

/**
 * SVN Inventory
 */
class SvnInventory extends SvnClient
{

    #[Inject(Cli::class)]
    protected Cli $cli;


    /**
     * Get inventory
     */
    public function get(localPackage $pkg, string $dir_name = 'trunk', int $rev_id = 0):array
    {

        // Initialize
        $svn = $pkg->getSvnRepo();

        // Get inventory
        if (!$res = $svn->getProperty('inventory', $dir_name, $rev_id)) { 
            throw new ApexSvnRepoException("Unable to obtain inventory of $url, error: " . $this->error_output);
        }
        $inv = json_decode($res, true);

        // Return
        return $inv['files'];
    }

    /**
     * Compare
     */
    public function compare(LocalPackage $pkg, array $inv_c):string
    {

        // Get difference
        $inv = $this->get($pkg);
        $diff = array_diff_assoc($inv, $inv_c);

        // Check diff
        if (count($diff) == 0) { 
            return 'use_remote';
        }

        // List differences
        $this->cli->send("\r\n");
        $this->cli->send("The following files are out of sync between the repository and local machine:\r\n\r\n");
        foreach ($diff as $file => $hash) { 
            $this->cli->send("    $file\r\n");
        }
        $this->cli->send("\r\n");

        // Set options
        $options = [
            'use_remote' => 'Use files from repository.',
            'use_local' => 'Use files on local machine.',
            'rename' => 'Rename files on local machine, and use files from repository.',
            'cancel' => 'Cancel operation, and exit.'
        ];

        // Get option
        $option = $this->cli->getOption("How would you like to handle the file differences?", $options, '', true);
        if ($option == 'cancel') { 
            $this->cli->send("Ok, goodbye.\r\n\r\n");
        }
        return $option;
    }

}



