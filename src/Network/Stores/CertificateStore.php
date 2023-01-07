<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\HttpClient;
use Nyholm\Psr7\Request;
use Apex\App\Exceptions\ApexCertificateNotExistsException;

/**
 * Certificate store
 */
class CertificateStore extends AbstractStore
{

    /**
     * constructor
     */
    public function __construct(
        private HttpClient $http, 
        private string $confdir = ''
    ) { 

        // Get confdir
        if ($this->confdir == '') { 
            $this->confdir = $this->determineConfDir();
        }
    }

    /**
     * Get certificate
     */
    public function get(string $name, bool $force_download = false):string
    {

        // Check for local file
        $crt_file = $this->confdir . '/certs/' . $name . '.crt';
        if ($force_download === false && file_exists($crt_file)) { 
            $crt = file_get_contents($crt_file);
            return $crt;
        }

        // Get it from ledger
        $req = new Request('GET', 'https://ledger.apexpl.io/api/ledger/crt/' . $name);
        $res = $this->http->sendRequest($req);

        // Check response
        if (!$json = json_decode($res->getBody()->getContents(), true)) { 
            throw new ApexCertificateNotExistsException("Did not receive JSON object from ledger server, instead got: " . $res->getBody());
        } elseif ($res->getStatusCode() != 200) { 
            throw new ApexCertificateNotExistsException("Received a " . $res->getStatusCode() . " from ledger server when trying to retrieve '$name' certificate with message, " . $json['message']);
        } elseif ($json['status'] != 'ok') { 
            throw new ApexCertificateNotExistsException("Unable to obtain certificate '$crt_name' from ledger, received non-ok status with message, $json[status]");
        }

        // Create /certs/ directory, if needed
        if (!is_dir(dirname($crt_file))) { 
            mkdir(dirname($crt_file), 0755, true);
        }

        // Save certificate
        file_put_contents($crt_file, $json['data']['certificate']);
        return $json['data']['certificate'];
    }

}




