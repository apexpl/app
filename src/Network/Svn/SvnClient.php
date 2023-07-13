<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\Container;
use Apex\App\Cli\CLi;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\RsaKeyStore;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;

/**
 * SVN Client
 */
class SvnClient
{

    // Dependencies, defined by SvnRepo class
    public LocalPackage $pkg;
    protected Container $cntr;
    protected Cli $cli;
    protected RsaKeyStore $rsa_store;

    // Properties
    protected array $error_codes = [];
    public string $error_output = '';
    protected ?bool $is_public = null;

    // Target properties
    protected string $t_dir = 'trunk';
    protected int $t_revid = 0;
    protected bool $t_local = false;
    protected bool $t_ssh = true;
    protected bool $t_local_repo = false;
    protected string $t_rootdir = '';

    /**
     * Set target
     */
    public function setTarget(string $dir_name = 'trunk', int $rev_id = 0, bool $is_local = false, bool $is_ssh = true, string $rootdir = '', bool $is_local_repo = false):void
    {
        $this->t_dir = $dir_name;
        $this->t_revid = $rev_id;
        $this->t_local = $is_local;
        $this->t_ssh = $is_ssh;
        $this->t_local_repo = $is_local_repo;
        $this->t_rootdir = $rootdir;
    }

    /**
     * Create args
     */
    public function createArgs(array $args = [], array $options = []):array
    {

        // Get base target
        $target = match (true) { 
            $this->t_local_repo => 'file://localhost/svn',
            $this->t_local => '', 
            $this->t_ssh => 'svn+ssh://svn@' . $this->pkg->getRepo()->getSvnHost(), 
            default => 'svn://' . $this->pkg->getRepo()->getSvnHost()
        };

        // Add dir name, if not local
        if ($this->t_local === false) {
            $target .= '/' . $this->pkg->getAuthor() . '/' . $this->pkg->getAlias() . '/' . trim($this->t_dir, '/');
        }

        // Start args
        array_unshift($args, 'svn');
        if ($target != '') { 
            $args[] = $target;
        }

        // Add revision id, if needed
        if ($this->t_revid > 0) { 
            array_push($args, '-r', $this->t_revid);
        }

        // Add options
        if (count($options) > 0) { 
            array_push($args, ...$options);
        }

        // Return
        return $args;
    }

    /**
     * Get svn url
     */
    public function getSvnUrl(string $dir_name = '', bool $is_public = true):string
    {

        // Start url
        if ($is_public === true) { 
            $url = 'svn://' . $this->pkg->getRepo()->getSvnHost();
        } else { 
            $url = 'svn+ssh://svn@' . $this->pkg->getRepo()->getSvnHost();
        }

        // Finish url
 $url .= '/' . $this->pkg->getAuthor() . '/' . $this->pkg->getAlias();
        if ($dir_name != '') { 
            $url .= '/' . trim($dir_name, '/') . '/';
        }

        // Return
        return $url;
    }

    /**
     * Exec command
     */
    public function exec(array $args, array $options = [], bool $buffer_output = false):?string
    {

        // Initialize
        $this->error_output = '';
        $this->error_codes = [];
        $args = $this->createArgs($args, $options);

        // Check ssh key, if needed
        if ($this->t_ssh === true && $this->t_local === false) {
            $this->checkSshAgent();
        }

        // Run process
        $process = new Process($args);
        $process->setTimeout(600);
        if ($this->t_local === true && $this->pkg->getType() == 'project') { 
            $rootdir = SITE_PATH;
            $process->setWorkingDirectory(SITE_PATH);
        } elseif ($this->t_local === true) { 
            $rootdir = $this->t_rootdir == '' ? SITE_PATH . '/.apex/svn/' : rtrim($this->t_rootdir, '/') . '/' . $this->pkg->getAuthor();
            $process->setWorkingDirectory($rootdir . $this->pkg->getAlias());
        }

        // Run
        if ($buffer_output === true) { 
            $process->run(function ($type, $buffer) { 
                $this->cli->send($buffer);
            });
        } else { 
            $process->run();
        }

        // Check if successful
        if ($process->isSuccessful() === true) {
            if ($this->t_ssh === false) { 
                $this->is_public = true;
            }
            return $process->getOutput();
        }

        // Get error 
        $this->error_output = $process->getErrorOutput();
        $lines = explode("\n", $this->error_output);

        // Parse error lines, get codes
        foreach ($lines as $line) { 

            if (!preg_match("/svn: (E|W)(\d+?):/", $line, $match)) { 
                continue;
            }
            $this->error_codes[] = $match[2];
        }

        // Return
        return null;
    }

    /**
     * Check SSH agent
     */
    public function checkSshAgent():void
    {

        // Get SSH key
        $ssh_key = $this->pkg->getLocalAccount()->getSshKey();
        $ssh_hash = $this->rsa_store->get($ssh_key)->getSha256();

        // Get lines of loaded keys
        $process = new Process(['ssh-add', '-l']);
        $lines = $process->run();
        $lines = explode("\n", $process->getOutput());

        // Get hashes of loaded keys
        $loaded = [];
        foreach ($lines as $line) { 
            if (!preg_match("/SHA256:(.+?)\s/", $line, $m)) { 
                continue;
            }
            $loaded[] = trim($m[1]);
        }

        // Load key, if needed
    if (!in_array($ssh_hash, $loaded)) { 
            $this->rsa_store->addSshAgent($ssh_key);
        }

    }

    /**
     * Get package
     */
    public function getPackage():?LocalPackage
    {
        return $this->pkg;
    }

}

