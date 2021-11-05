<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;
use Apex\Svc\Container;
use Apex\App\Cli\Cli;
use Apex\App\Network\Models\{LocalPackage, Repo};
use Apex\App\Network\Stores\RsaKeyStore;
use Apex\App\Exceptions\ApexSvnRepoException;
use Apex\App\Attr\Inject;

/**
 * SVN Repo
 */
class SvnRepo extends SvnClient
{

    /**
     * Constructor
     */
    public function __construct(
        LocalPackage $pkg, 
        Container $cntr, 
        Cli $cli, 
        RsaKeyStore $rsa_store
    ) {
        $this->pkg = $pkg;
        $this->cli = $cli;
        $this->cntr = $cntr;
        $this->rsa_store = $rsa_store;
    }


    /**
     * Info
     */
    public function info():?array
    {

        // Get process
        $res = $this->exec(['info']);

        // If repo not exists
        if ($res === null && (in_array('210005', $this->error_codes) || in_array('200009', $this->error_codes))) { 
            return null;
        } elseif ($res === null) { 
            throw new ApexSvnRepoException("Unknown error received by SVN server while trying to obtain repository info.  Error: " . $this->error_output);
        }
        $lines = explode("\n", $res);

        // Parse lines
        $info = [];
        foreach ($lines as $line) {
            if (!str_contains($line, ':')) { 
                continue;
            }
            list($key, $value) = explode(':', $line, 2);
            $info[str_replace(' ', '_', strtolower($key))] = trim($value);
        }

        // Return
        return $info;
    }

    /**
     * Checkout
     */
    public function checkout(string $dir_name = 'trunk', int $rev_id = 0):bool
    {
        $svn = $this->cntr->make(SvnCheckout::class);
        return $svn->process($this, $dir_name, $rev_id);
    }

    /**
     * Get current branch
     */
    public function getCurrentBranch():string
    {

        // Get info
        $this->setTarget('trunk', 0, true);
        if (!$info = $this->info()) { 
            return null;
        } elseif (!isset($info['relative_url'])) { 
            return null;
        }

        // Get branch and return
        $branch = preg_replace("/^\^\//", '', $info['relative_url']);
        return $branch;
    }

    /**
     * Commit
     */
    public function commit(string $message = '', string $commit_file = ''):void
    {
        $svn = $this->cntr->make(SvnCommit::class);
        $svn->commit($this->pkg, $message, $commit_file);
    } 

    /**
     * Copy
     */
    public function copy(string $source, string $dest, array $commit_args):void
    {

        // Set target, and get destination URL
        $this->setTarget($source);
        $dest_url = $this->getSvnUrl($dest, false);

        // Create options
        $options = [$dest_url];
        array_push($options, ...$commit_args);

        // Copy
        if (!$this->exec(['copy'], $options)) { 
            throw new ApexSvnRepoException("Unable to copy SVN repo from $source to $dest, error: " . $this->error_output);
        }

    }

    /**
     * rm
     */
    public function rmdir(string $dir_name, string $message = '', string $commit_file = ''):void
    {

        // Delete
        $this->setTarget($dir_name);

        // Get commit message args
        if ($message == '' && $commit_file == '') { 
            $message = "Deleting directory, $dir_name";
        }
        $options = $commit_file == '' ? ['-m', $message] : ['--file', $commit_file];

        // Remove dir
        if (!$this->exec(['rm'], $options)) { 
            throw new ApexSvnRepoException("Unable to delete directory, $dir_name, error: " . $this->error_output);
        }

    }

    /**
     * Switch
     */
    public function switch(string $dir_name):void
    {

        // Initialize
        $this->setTarget('', 0, true);
        $url = $this->getSvnUrl($dir_name, false);

        // Switch
        if (!$this->exec(['switch', $url])) { 
            throw new ApexSvnRepoException("Unable to switch to branch, $dir_name, error: " . $this->error_output);
        }

    }

    /**
     * Get property
     */
    public function getProperty(string $name, string $dir_name = 'trunk', int $rev_id = 0, bool $is_local = false):?string
    {

        // Set target, try public first
        $this->setTarget($dir_name, $rev_id, $is_local, false);
        if (!$value = $this->exec(['pget', $name])) { 
            if ($is_local === false && $this->is_public !== true) { 
                $this->setTarget($dir_name, $rev_id);
                $value = $this->exec(['pget', $name]);
            }
        }

        // Check value
        if (!$value) { 
            return null;
        }

        // Return
        return trim($value);
    }

    /**
     * Set property
     */
    public function setProperty(string $name, string $value, bool $use_file = false):void
    {

        // Start args
        $args = ['pset', $name];

        // Check for use_file
        if ($use_file === true) { 
            $tmp_file = tempnam(sys_get_temp_dir(), 'apex');
            file_put_contents($tmp_file, $value);
            array_push($args, '--file', $tmp_file);
        } else { 
            $args[] = $value;
        }
        $args[] = './';

        // Set property
        if (!$this->exec($args)) { 
            throw new ApexSvnRepoException("Unable to set property $name, error: " . $this->error_output);
        }

        // Delete tmp_file, if needed
        if ($use_file === true) { 
            unlink($tmp_file);
        }
    }

    /**
     * Get all releases
     */
    public function getReleases():?array
    {

        // Check public first
        $this->setTarget('tags', 0, false, false);

        // List public tags
        if (!$res = $this->exec(['list'])) { 
            $this->setTarget('tags');
            $res = $this->exec(['list']);
        }

        // Check for error
        if (!$res) { 
            return null;
        }

        // Get latest release
        $tags = explode("\n", trim($res));
        $tags = array_map( fn($version) => rtrim(trim($version), '/'), $tags);

        // Sort and release
        usort($tags, function ($a, $b) { return version_compare($a, $b, '>') ? 1 : -1; });
        return $tags;
    }

    /**
     * Get latest release
     */
    public function getLatestRelease():?string
    {

        // Get releases
        if (!$tags = $this->getReleases()) { 
            return null;
        }

        // Return
        $version = array_pop($tags);
        return $version;
    }

    /**
     * Add
     */
    public function add(...$files):void
    {

        // Go through files
        foreach ($files as $file) { 

            if (!$res = $this->exec(['add'], [$file])) { 
                throw new ApexSvnRepoException("Unable to add file '$file' to SVN repo, error: " . $this->error_output);
            }
        }

    }

    /**
     * Remove files
     */
    public function rm(...$files):void
    {

        // Go through files
        foreach ($files as $file) { 

            $this->exec(['rm'], [$file]);
        }

    }

}

