<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\{Convert, Container};
use Apex\App\Network\Models\{RsaKey, LocalPackage};
use Apex\App\Exceptions\ApexCertificateNotExistsException;

/**
 * SSH Keys Store
 */
class RsaKeyStore extends AbstractStore
{

    // Properties
    private array $loaded = [];
    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Constructor
     */
    public function __construct(
        private string $confdir = ''
    ) { 

        // Get confdir
        if ($this->confdir == '') { 
            $this->confdir = $this->determineConfDir();
        }

        // Check /keys/ directory exists
        if (!is_dir("$this->confdir/keys/pub")) { 
            mkdir($this->confdir . "/keys/pub", 0755, true);
        }
    }

    /**
     * List
     */
    public function list():array
    {

        // Scan directory
        $keys = [];
        $files = scandir("$this->confdir/keys");
        foreach ($files as $file) { 

            // Check for .pem
            if (!preg_match("/^(.+)\.pem$/", $file, $m)) { 
                continue;
            }
            $info = stat($this->confdir . "/keys/$file");

            // Get name
            $keys[$m[1]] = 'Created: ' . $this->convert->date(date('Y-m-d H:i:s', $info['ctime'])   , true);

            // Check if password protected
            $text = file_get_contents($this->confdir . "/keys/$file");
            if (!openssl_pkey_get_private($text)) { 
                $keys[$m[1]] .= ' (Password protected)';
            }
        }

        // Return
        return $keys;
    }

    /**
     * Get RSA key
     */
    public function get(string $alias):?RsaKey
    {

        // Check if loaded
        if (isset($this->loaded[$alias])) { 
            return $this->loaded[$alias];
        } elseif (!file_exists($this->confdir . "/keys/$alias.pem")) { 
            return null;
        }

        // Load rsa key
        $rsa = $this->cntr->make(RsaKey::class, [
            'alias' => $alias, 
            'private_key' => file_get_contents($this->confdir . "/keys/$alias.pem"), 
            'public_key' => file_get_contents($this->confdir . "/keys/pub/$alias.pub"), 
            'ssh_pubkey' => file_get_contents($this->confdir . "/keys/ssh/$alias.ssh"), 
            'privkey' => null
        ]);

        // Return
        return $rsa;
    }

    /**
     * Save
     */
    public function save(string $alias, RsaKey $rsa, bool $overwrite = false):bool
    {

        // Check if exists
        $alias = strtolower($alias);
        if ($overwrite === false && file_exists($this->confdir . "/keys/$alias.pem")) {
            return false;
        }

        // Create /ssh/ directory, if needed
        if (!is_dir($this->confdir . '/keys/ssh')) { 
            mkdir($this->confdir . '/keys/ssh', 0755, true);
        }

        // Create /pub/ directory, if needed
        if (!is_dir($this->confdir . '/keys/pub')) { 
            mkdir($this->confdir . '/keys/pub', 0755, true);
        }

        // Save files
        file_put_contents($this->confdir . "/keys/$alias.pem", $rsa->getPrivateKey());
        file_put_contents($this->confdir . "/keys/pub/$alias.pub", $rsa->getPublicKey());
        file_put_contents($this->confdir . "/keys/ssh/$alias.ssh", $rsa->getPublicSshKey());

        // Set properties
        $rsa->setAlias($alias);
        $this->loaded[$alias] = $rsa;

        // Chmod file
        chmod($this->confdir . "/keys/$alias.pem", 0600);
        return true;
    }

    /**
     * Delete
     */
    public function delete(string $alias):void
    {

        // Set files
        $files = [
            $this->confdir . "/keys/$alias.pem", 
            $this->confdir . "/keys/ssh/$alias.ssh", 
            $this->confdir . "/keys/pub/$alias.pub"
        ];

        // Delete files
        foreach ($files as $file) { 

            if (!file_exists($file)) { 
                continue;
            }
            unlink($file);
        }

    }

    /**
     * Has
     */
    public function has(string $alias):bool
    {
        $file = $this->confdir . '/keys/' . $alias . '.pem';
        return file_exists($file);
    }

    /**
     * Add to ssh-agent
     */
    public function addSshAgent(string $alias):bool
    {

        // Check file
        if (!file_exists($this->confdir . "/keys/$alias.pem")) { 
            return false;
        }

        // Add to ssh-agent
        passthru("ssh-add " . $this->confdir . "/keys/$alias.pem");
        return true;
    }

    /**
     * Get signing certificate
     */
    public function getSigningCertificate(LocalPackage $pkg):string
    {

        // Get filename
        $filename = $pkg->getLocalUser() . '.' . $pkg->getRepoAlias();
        if ($pkg->getAuthor() != $pkg->getLocalUser()) { 
            $filename .= '.' . $pkg->getAuthor();
        }
        $filename .= '.crt';

        // Check file exists
        if (!file_exists($this->confdir . '/certs/' . $filename)) { 
            throw new ApexCertificateNotExistsException("Signing certificate does not exist, $filename");
        }

        // Return
        return file_get_contents($this->confdir . '/certs/' . $filename);
    }

    /**
     * Get all SHA256 hashes
     */
    public function getSha256Hashes():array
    {

        // Get keys
        $hashes = [];
        foreach ($this->list() as $alias) { 
            $rsa = $this->get($alias);
            $hashes[$alias] = $rsa->getSha256();
        }

        // Return
        return $hashes;
    }

}



