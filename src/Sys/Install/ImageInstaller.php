<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Install;

use Apex\App\App;
use Apex\Svc\{HttpClient, Db, Convert};
use Apex\App\Sys\Install\YamlInstaller;
use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\Io;
use Nyholm\Psr7\Request;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use ZipArchive;
use redis;

/**
 * Installation images
 */
class ImageInstaller
{

    /**
     * Install image
     */
    public static function install(string $name, App $app, Cli $cli):void
    {


        // Download
        if (!$tmp_dir = self::download($name, $app, $cli)) { 
            return;
        }

        // Load yaml file
        if (!$yaml = self::loadYaml($tmp_dir, $cli)) { 
            return;
        }

        // Install repos
        YamlInstaller::installRepos($yaml, $app);

        // Install packages
        YamlInstaller::installPackages($yaml, $app, $cli);

        // Set config vars
        YamlInstaller::setConfigVars($yaml, $app);

        // Complete installation
        self::complete($tmp_dir, $name, $app, $cli);
    }

    /**
     * Download
     */
    private static function download(string $name, App $app, Cli $cli):?string
    {

        // Initialize
        $http = $app->getContainer()->get(HttpClient::class);
        $io = $app->getContainer()->make(Io::class);


        // Create request
        $url = 'https://images.apexpl.io/download/' . $name . '.zip';
        $req = new Request('GET', $url);

        // Send http request
        $res = $http->sendRequest($req);

        // Check status
        if ($res->getStatusCode() != 200) { 
            $cli->error("Unable to download the installation image, $name");
            return null;
        }
        $body = $res->getBody();

        // Get tmp dir
        $tmp_dir = sys_get_temp_dir() . '/apex-' . uniqid();
        $io->createBlankDir($tmp_dir);

        // Save archive
        $fh = fopen($tmp_dir . '/image.zip', 'wb');
        while (!$body->eof()) { 
            fwrite($fh, $body->read(2048));
        }
        fclose($fh);

        // Unzip archive
        $zip = new ZipArchive();
        if (!$zip->open($tmp_dir . '/image.zip')) { 
            $cli->error("Unable to open zip file at $tmp_dir/image.zip");
            return null;
        }
        $zip->extractTo($tmp_dir . '/');
        $zip->close();

        // Return
        return $tmp_dir;
    }

    /**
     * Load yaml file
     */
    private static function loadYaml(string $tmp_dir, Cli $cli):?array
    {

        // Check file exists
        $file = $tmp_dir . '/config.yml';
        if (!file_exists($file)) { 
            $cli->error("No yaml file exists at $tmp_dir/config.yml");
            return null;
        }

        // Parse file
        try {
            $yaml = Yaml::parseFile($file);
        } catch (ParseException $e) { 
            $cli->error("Unable to parse yaml file at $tmp_dir/config.yml.  Error: " . $e->getMessage());
            return null;
        }

        // Return
        return $yaml;
    }

    /**
     * Complete
     */
    private static function complete(string $tmp_dir, string $name, App $app, Cli $cli):void
    {

        // Check image.php file exists
        if (!file_exists("$tmp_dir/image.php")) { 
            $cli->error("No image.php file exists at $tmp_dir/image.php");
            return;
        }

        // Get class name
        $convert = $app->getContainer()->make(Convert::class);
        list($author, $alias) = explode('/', $name, 2);
        $class_name = "\\Images\\" . $convert->case($alias, 'title') . "\\image";
        // Load image.php file
        require_once("$tmp_dir/image.php");
        $obj = $app->getContainer()->make($class_name);

        // Run installation
        $db = $app->getContainer()->get(Db::class);
        $redis = $app->getContainer()->get(redis::class);
        $obj->install($app, $db, $redis);

        // Send message
        $cli->send("Successfully installed the image, $name\r\n\r\n");
    }

}


