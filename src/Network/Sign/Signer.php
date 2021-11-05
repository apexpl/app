<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\App\Cli\Cli;
use Apex\App\Network\Models\{LocalPackage, RsaKey};
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Network\Stores\{RsaKeyStore, CertificateStore};
use Apex\App\Exceptions\ApexCertificateNotExistsException;
use Apex\App\Attr\Inject;
use OpenSSLAsymmetricKey;

/**
 * SIgner
 */
class Signer extends AbstractSigner
{

    #[Inject(MerkleTreeBuilder::class)]
    private MerkleTreeBuilder $tree_builder;

    #[Inject(RsaKeyStore::class)]
    private RsaKeyStore $key_store;

    #[Inject(CertificateStore::class)]
    private CertificateStore $cert_store;

    /**
     * Create signature
     */
    public function signPackage(SvnRepo $svn):?string
    {

        // Get current branch
        $pkg = $svn->getPackage();
        $branch = $svn->getCurrentBranch();

        // Get current merkle root
        $svn->setTarget($branch, 0, true);
        $prev_merkle_root = $svn->getProperty('merkle_root');

        // Build merkle root
        $this->cli->send("Generating merkle root... ");
        $tree = $this->tree_builder->buildPackage($pkg, $prev_merkle_root);
        $this->cli->send("done.\r\n");

        // Get signing key
        $rsa = $this->key_store->get($pkg->getLocalAccount()->getSignKey());
        $privkey = $this->unlockPrivateKey($rsa);
        $rsa->setPrivkey($privkey);

        // Ensure we have certificate
        $crt_name = $pkg->getCrtName();
        try { 
            $crt = $this->cert_store->get($crt_name);
        } catch (ApexCertificateNotExistsException $e) { 
            $this->cli->error("There is no signing certificate available for this user and package.  Please request a signing certificate first, see 'apex help account certs request' for details.");
            return null;
        }

        // Sign merkle root
        $this->cli->send("Signing package... ");
        openssl_sign($tree->getMerkleRoot(), $signature, $privkey, 'sha384');
        $signature = bin2hex($signature);

        // Set properties
        $svn->setTarget($branch, 0, true);
        $svn->setProperty('merkle_root', $tree->getMerkleRoot());
        $svn->setProperty('signature', $signature);
        $svn->setProperty('inventory', json_encode($tree->toArray()), true);
        if ($prev_merkle_root !== null) { 
            $svn->setProperty('prev_merkle_root', $prev_merkle_root);
        }

        // Return signature
        $this->cli->send("done\r\n");
        return $signature;
    }

}

