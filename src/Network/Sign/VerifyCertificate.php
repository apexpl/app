<?php
declare(strict_types = 1);

namespace Apex\App\Network\Sign;

use Apex\Svc\HttpClient;
use Apex\App\Cli\CLi;
use Apex\App\Network\Stores\CertificateStore;
use Nyholm\Psr7\Request;
use Apex\App\Exceptions\{ApexVerificationException, ApexLedgerException};
use Apex\App\Attr\Inject;
use DateTime;

/**
 * Verify cert issuer
 */
class VerifyCertificate
{

    #[Inject(CertificateStore::class)]
    private CertificateStore $store;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

    #[Inject(Cli::class)]
    private Cli $cli;

    /**
     * Verify
     */
    public function verify(string $crt, string $crt_name, ?DateTime $release_date = null):bool
    {

        // Initialize
        $common_name = preg_replace("/\.([a-zA-Z0-9-_]+)$/", '@' . "\\1", $crt_name);
        $parts = explode('.', $crt_name);
        if (count($parts) < 2) { 
            throw new ApexVerificationException("Invalid common name for verification, it's too short, $common_name");
        }
        array_shift($parts);

        // Check common name of certificate
        if (!$details = openssl_x509_parse($crt)) { 
            throw new ApexVerificationException("Unable to read certificate, $crt_name");
        } elseif ($details['subject']['CN'] != $common_name) { 
            throw new ApexVerificationException("Common name does not match in certificate, expecting '$common_name' and got '" . $details['subject']['CN'] . "' instead.");
        }

        // Start fingerprints
        $fingerprints = [$crt_name => openssl_x509_fingerprint($crt, 'sha384')];

        // Check issuer certs
        $chk_crt = $crt;
        while (count($parts) > 0) { 

            // Skip if ca@repo
            if (preg_match("/^ca\.[a-zA-z0-9-_]+$/", $crt_name)) { 
                break;
            }

            // Get issuer crt
            $issuer_crt_name = count($parts) > 1 ? implode('.', $parts) : 'ca.' . $parts[0];
            if (!$issuer_crt = $this->store->get($issuer_crt_name)) { 
                throw new ApexVerificationException("Unable to retrieve issuer certificate with common name, $issuer_crt_name");
            }
            $fingerprints[$issuer_crt_name] = openssl_x509_fingerprint($issuer_crt, 'sha384');

            // Verify
            if (1 !== openssl_x509_verify($chk_crt, $issuer_crt)) {
                throw new ApexVerificationException("Unable to verify the certificate for '$crt_name' was issued by '$issuer_crt_name'");
            }
            array_shift($parts);
            $chk_crt = $issuer_crt;
        }

        // Check root crt
        $root_crt = $this->store->get('root.apex');
        if (1 !== openssl_x509_verify($chk_crt, $root_crt)) { 
            throw new ApexVerificationException("Unable to verify with root certificate.");
        }

    // Check revocations
    return $this->checkRevocations($fingerprints, $release_date);
    }

    /**
     * Check revocations
     */
    private function checkRevocations(array $fingerprints, ?DateTime $release_date = null):bool
    {

        // Set JSON request
        $json_req = json_encode([
            'release_date' => $release_date?->format('Y-m-d H:i:s'), 
            'fingerprints' => $fingerprints
        ]);

        // Check any revocations
        $request = new Request('POST', 'https://ledger.apexpl.io/api/ledger/verify_certificate', ['Content-type' => 'application/json'], $json_req);
        if (!$res = $this->http->sendRequest($request)) { 
            throw new ApexLedgerException("Did not receive a response from the Ledger server.");
        }

        // Check response
        if (!$json = json_decode($res->getBody()->getContents(), true)) { 
            throw new ApexLedgerException("Did not receive a JSON object from Ledger server, instead received: " . $res->getBody());
        } elseif ($res->getStatusCode() !== 200) {
            $message = $json['message'] ?? 'undefined';
            throw new ApexLedgerException("Received a " . $res->getStatusCode() . " code from the ledger server with message: $message");
        } elseif (!isset($json['data']['fail'])) { 
            throw new ApexLedgerException("Did not receive valid response from ledger, instead got: " . print_r($json));
        } elseif ($json['data']['fail'] > 0) { 
            $this->cli->sendHeader('Certificate Verification Failed');
            $this->cli->send("Unable to complete the install, as certificate verification has failed.  Below is the response of the verification request:\r\n\r\n");
            $this->cli->send(json_encode($json['data'], JSON_PRETTY_PRINT));
            return false;
        }

        // Return
        return true;
    }

}


