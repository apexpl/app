<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Stores\CertificateStore;
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Network\Sign\{MerkleTreeBuilder, VerifyCertificate};
use Apex\App\Network\Models\MerkleTree;
use Apex\App\Exceptions\ApexVerificationException;
use Apex\App\Attr\Inject;
use OpenSSLCertificate ;
use DateTime;

/**
 * Verify download
 */
class VerifyDownload
{

    #[Inject(CertificateStore::class)]
    private CertificateStore $store;

    #[Inject(MerkleTreeBuilder::class)]
    private MerkleTreeBuilder $tree_builder;

    #[Inject(VerifyCertificate::class)]
    private VerifyCertificate $verify_cert;

    /**
     * Verify download
     */
    public function verify(SvnRepo $svn, string $svn_dir, string $local_dir, ?MerkleTree $tree = null):?string
    {

        // Initialize
        $pkg = $svn->getPackage();
        $svn_url = $svn->getSvnUrl($svn_dir, true);

        // Get properties
        if (!$merkle_root = $svn->getProperty('merkle_root', $svn_dir)) { 
            throw new ApexVerificationException("Unable to obtain merkle root from SVN URL, $svn_url");
        } elseif (!$signature = $svn->getProperty('signature', $svn_dir)) { 
            throw new ApexVerificationException("Unable to obtain signature from SVN URL, $svn_url");
        }
        $prev_merkle_root = $svn->getProperty('prev_merkle_root', $svn_dir);

        // Get last author
        $info = $svn->info();
        $last_author = $info['last_changed_author'];
        list($date, $time, $junk) = explode(' ', $info['last_changed_date'], 3);
        $release_date = new DateTime("$date $time");

        // Get crt name
        $name = [$pkg->getAuthor(), $pkg->getRepoAlias()];
        if ($last_author != $pkg->getAuthor()) { 
            array_unshift($name, $last_author);
        }
        $crt_name = implode('.', $name);

        // Get certificate
        if (!$crt_text = $this->store->get($crt_name)) { 
            throw new ApexVerificationException("Unable to retrieve certificate for $crt_name");
        }

        // Verify
        $crt = openssl_x509_read($crt_text);
        if (!openssl_verify($merkle_root, hex2bin($signature), $crt, 'sha384')) { 
            throw new ApexVerificationException("Unable to verify digital signature on SVN URL, $svn_url");
        }

        // Build merkle root from files
        if ($tree === null) { 
            $tree = $this->tree_builder->buildPackage($pkg, $prev_merkle_root, $local_dir);
        }
        if ($tree->getMerkleRoot() != $merkle_root) { 
            throw new ApexVerificationException("Merkle root on SVN property does not match merkle root generated by files.");
        }

        // Verify issuer
        if (!$this->verify_cert->verify($crt_text, $crt_name, $release_date)) { 
            throw new \Exception("Unable to verify upstream of certificate, $crt_name");
            return null;
        }

        // Get signed by
        $details = openssl_x509_parse($crt_text);
        $signed_by = $details['subject']['CN'] . ' (' . $details['subject']['O'] . ')';

        // Return
        return $signed_by;
    }

}


