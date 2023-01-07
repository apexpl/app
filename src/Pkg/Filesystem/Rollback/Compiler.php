<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\Rollback;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Sys\Utils\Io;
use Apex\App\Attr\Inject;

/**
 * Rollback
 */
class Compiler
{

    #[Inject(Io::class)]
    private Io $io;

    // Properties
    private ?LocalPackage $pkg = null;
    private string $latest_version;
    private string $rollback_dir;
    private array $config;

    /**
     * Initialize
     */
    public function initialize(LocalPackage $pkg, string $latest_version):void
    {

        // Set properties
        $this->pkg = $pkg;
        $this->latest_version = $latest_version;

        // Create directory, if needed
        $this->rollback_dir = SITE_PATH . '/.apex/upgrades/' . $pkg->getAlias() . '/' . $latest_version;
        if (!is_dir($this->rollback_dir)) { 
            mkdir($this->rollback_dir, 0755, true);
        }

        // Start config
        $this->config = [
            'from_version' => $pkg->getVersion(),
            'created_at' => time(),
            'migrations' => [],
            'files_added' => []
        ];

    }

    /**
     * Add file
     */
    public function addFile(LocalPackage $pkg, string $local_file, string $svn_file):void
    {

        // Check for new file
        if (!file_exists($local_file)) { 
            $this->config['files_added'][] = $svn_file;
            return;
        }
        $svn_file = $this->rollback_dir . '/' . $svn_file;

        // Move file
        $this->io->rename($local_file, $svn_file);
    }

    /**
     * Save
     */
    public function save(array $migrations):void
    {

        // Get JSON
        $this->config['migrations'] = $migrations;
        $json = json_encode($this->config, JSON_PRETTY_PRINT);

        // Save file
        file_put_contents("$this->rollback_dir/config.json", $json);
    }

}


