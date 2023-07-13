<?php
declare(strict_types=1);

namespace Apex\App\Network\Svn;

use Apex\Svc\HttpClient;
use Apex\App\Network\Svn\SvnRepo;
use Apex\App\Sys\Utils\Io;
use Apex\App\Attr\Inject;
use ZipArchive;

/**
 * Svn Download License
 */
class SvnDownloadLicense
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(HttpClient::class)]
    private HttpClient $http;

    /**
     * Process
     */
    public function process(SvnRepo $svn, string $license_id):string
    {

        // Get download URL
        $api_url = $svn->pkg->getRepo()->getApiUrl();
        $url = $api_url . "repos/download?pkg_serial=" . urlencode($svn->pkg->getSerial()) . "&license_id=" . urlencode($license_id);

        // Get tmp file and directory
        $tmp_file = rtrim(sys_get_temp_dir(), '/') . '/' .  uniqid();
        $tmp_dir = rtrim(sys_get_temp_dir(), '/') . '/' .  uniqid();
        $this->io->createBlankDir($tmp_dir);

        // Download zip file
        $res = $this->http->get($url, ['sink' => fopen($tmp_file, 'w')]);
        if ($res->getStatusCode() != 200) {
            throw new \Exception("Unable to download zip file for package " . $svn->pkg->getSerial() . ", received http status code: " . $res->getStatusCode());
        } else if (!file_exists($tmp_file)) {
            throw new \Exception("Unable to download zip file for package " . $svn->pkg->getSerial() . ", unknown error.");
        }

        // Unpack .zip archive
        $zip = new ZipArchive;
        if (!$zip->open($tmp_file)) {
            throw new \Exception("Unable to open zip file at, $tmp_file");
        }
        $zip->extractTo($tmp_dir);
        $zip->close();

        // Return
        @unlink($tmp_file);
        return $tmp_dir;
    }

}

