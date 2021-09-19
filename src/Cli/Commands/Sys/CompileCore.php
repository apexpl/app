<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Compile core
 */
class CompileCore implements CliCommandInterface
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    // Templates
    private array $templates = [
        'container.php' => 'PD9waHAKCnVzZSBMZWFndWVcRmx5c3lzdGVtXEZpbGVzeXN0ZW07CnVzZSBBcGV4XERiXEludGVyZmFjZXNcRGJJbnRlcmZhY2U7CnVzZSBBcGV4XENsdXN0ZXJcSW50ZXJmYWNlc1xCcm9rZXJJbnRlcmZhY2U7CnVzZSBBcGV4XEFwcFxJbnRlcmZhY2VzXFJvdXRlckludGVyZmFjZTsKdXNlIEFwZXhcTWVyY3VyeVxJbnRlcmZhY2VzXHtFbWFpbGVySW50ZXJmYWNlLCBTbXNDbGllbnRJbnRlcmZhY2UsIEZpcmViYXNlQ2xpZW50SW50ZXJmYWNlLCBXc0NsaWVudEludGVyZmFjZX07CnVzZSBQc3JcTG9nXExvZ2dlckludGVyZmFjZTsKdXNlIFBzclxDYWNoZVxDYWNoZUl0ZW1Qb29sSW50ZXJmYWNlOwp1c2UgUHNyXEh0dHBcQ2xpZW50XENsaWVudEludGVyZmFjZSBhcyBIdHRwQ2xpZW50SW50ZXJmYWNlOwp1c2UgQXBleFxDb250YWluZXJcSW50ZXJmYWNlc1xBcGV4Q29udGFpbmVySW50ZXJmYWNlOwp1c2UgTW9ub2xvZ1xMb2dnZXI7CnVzZSBNb25vbG9nXEhhbmRsZXJcU3RyZWFtSGFuZGxlcjsKCnJldHVybiBbCiAgICBEYkludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcRGJcRHJpdmVyc1xteVNRTFxteVNRTDo6Y2xhc3MsIAogICAgQ2FjaGVJdGVtUG9vbEludGVyZmFjZTo6Y2xhc3MgPT4gbnVsbCwgCiAgICBIdHRwQ2xpZW50SW50ZXJmYWNlOjpjbGFzcyA9PiBmdW5jdGlvbiAoKSB7IHJldHVybiBuZXcgXEd1enpsZUh0dHBcQ2xpZW50KFsndmVyaWZ5JyA9PiBmYWxzZV0pOyB9LCAKICAgIEJyb2tlckludGVyZmFjZTo6Y2xhc3MgPT4gQXBleFxDbHVzdGVyXEJyb2tlcnNcTG9jYWw6OmNsYXNzLCAKICAgIEZpbGVzeXN0ZW06OmNsYXNzID0+IGZ1bmN0aW9uKCkgeyAKICAgICAgICByZXR1cm4gXEFwZXhcU3RvcmFnZVxTdG9yYWdlOjppbml0KCdsb2NhbCcsIFsnZGlyJyA9PiBTSVRFX1BBVEggLiAnL3N0b3JhZ2UnXSk7CiAgICB9LAoKICAgIFJvdXRlckludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcQXBwXEJhc2VcUm91dGVyXFJvdXRlcjo6Y2xhc3MsCgogICAgTG9nZ2VySW50ZXJmYWNlOjpjbGFzcyA9PiBmdW5jdGlvbigpIHsgCiAgICAgICAgJGxvZ2RpciA9IF9fRElSX18gLiAnLy4uL3N0b3JhZ2UvbG9ncyc7CiAgICAgICAgcmV0dXJuIG5ldyBMb2dnZXIoJ2FwcCcsIFsKICAgICAgICAgICAgbmV3IFN0cmVhbUhhbmRsZXIoJGxvZ2RpciAuICcvZGVidWcubG9nJywgTG9nZ2VyOjpERUJVRyksIAogICAgICAgICAgICBuZXcgU3RyZWFtSGFuZGxlcigkbG9nZGlyIC4gJy9hcHAubG9nJywgTG9nZ2VyOjpJTkZPKSwgCiAgICAgICAgICAgIG5ldyBTdHJlYW1IYW5kbGVyKCRsb2dkaXIgLiAnL2Vycm9yLmxvZycsIExvZ2dlcjo6RVJST1IpIAogICAgICAgIF0pOwogICAgfSwgCgogICAgQXBleENvbnRhaW5lckludGVyZmFjZTo6Y2xhc3MgPT4gZnVuY3Rpb24oKSB7IAogICAgICAgIHJldHVybiBuZXcgXEFwZXhcQ29udGFpbmVyXENvbnRhaW5lcih1c2VfYXR0cmlidXRlczogdHJ1ZSk7CiAgICB9LCAKCiAgICBFbWFpbGVySW50ZXJmYWNlOjpjbGFzcyA9PiBcQXBleFxNZXJjdXJ5XEVtYWlsXEVtYWlsZXI6OmNsYXNzLAogICAgU21zQ2xpZW50SW50ZXJmYWNlOjpjbGFzcyA9PiBcQXBleFxNZXJjdXJ5XFNNU1xOZXhtbzo6Y2xhc3MsCiAgICBGaXJlYmFzZUNsaWVudEludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcTWVyY3VyeVxGaXJlYmFzZVxGaXJlYmFzZTo6Y2xhc3MsCiAgICBXc0NsaWVudEludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcTWVyY3VyeVxXZWJzb2NrZXRcV3NDbGllbnQ6OmNsYXNzLAoKICAgICdzeXJ1cy5jYWNoZV90dGwnID0+IDMwMCwgCiAgICAnY2x1c3Rlci50aW1lb3V0X3NlY29uZHMnID0+IDMsICAKICAgICdhcm1vci5jb29raWVfcHJlZml4JyA9PiAnYXJtb3JfJywgCiAgICAnYXJtb3IuY29va2llJyA9PiBbCiAgICAgICAgJ3BhdGgnID0+ICcvJywgCiAgICAgICAgJ2RvbWFpbicgPT4gJ35kb21haW5+JywgCiAgICAgICAgJ3NlY3VyZScgPT4gdHJ1ZSwgCiAgICAgICAgJ2h0dHBvbmx5JyA9PiBmYWxzZSwKICAgICAgICAnc2FtZXNpdGUnID0+ICdub25lJwogICAgXSAKXTsKCgo=',
        'env' => 'CiMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgQXBleCAuZW52IGZpbGUuCiMKIyBBcGV4IGhhcyBub3QgeWV0IGJlZW4gaW5zdGFsbGVkLCBoZW5jZSB0aGlzIGZpbGUgaXMgZW1wdHkuCiMKIyBQbGVhc2UgcnVuIC4vYXBleCB3aXRoaW4gdGhpcyBkaXJlY3RvcnkgdG8gaW5pdGlhdGUgdGhlIGluc3RhbGxhdGlvbiB3aXphcmQuCiMKIyMjIyMjIyMjIyMjIyMjIyMjIyMKCgo=',
        'routes.yml' => 'CiMjIyMjIyMjIyMKIyBSb3V0ZXMKIwojIFRoaXMgZmlsZSBoYXMgYmVlbiBhdXRvLWdlbmVyYXRlZCwgYnV0IHlvdSBtYXkgbW9kaWZ5IGFzIGRlc2lyZWQgYmVsb3cuICBQbGVhc2UgcmVmZXIgdG8gdGhlIGRldmVsb3BlciAKIyBkb2N1bWVudGF0aW9uIGZvciBkZXRhaWxzIG9uIHRoZSBlbnRyaWVzIHdpdGhpbiB0aGlzIGZpbGUuCiMjIyMjIyMjIyMKCnJvdXRlczoKICAgIGRlZmF1bHQ6IFB1YmxpY1NpdGUKCgo=',
        'site.yml' => 'CiMjIyMjIyMjIyMKIyBTaXRlIENvbmZpZwojCiMgVGhpcyBmaWxlIGhhcyBiZWVuIGF1dG8tZ2VuZXJhdGVkLCBidXQgeW91IG1heSBtb2RpZnkgYXMgZGVzaXJlZCBiZWxvdy4gIFBsZWFzZSByZWZlciB0byB0aGUgZGV2ZWxvcGVyIAojIGRvY3VtZW50YXRpb24gZm9yIGRldGFpbHMgb24gdGhlIGVudHJpZXMgd2l0aGluIHRoaXMgZmlsZS4KIyMjIyMjIyMjIwoKdGhlbWVzOgogICAgZGVmYXVsdDogZGVmYXVsdAoKdXNlcl90eXBlczoKCnBhZ2VfdmFyczoKCiAgICB0aXRsZToKICAgICAgICBkZWZhdWx0OiBBcGV4CgogICAgbGF5b3V0czoKICAgICAgICBpbmRleC5odG1sOiBob21lcGFnZQogICAgICAgIGRlZmF1bHQ6IGRlZmF1bHQKCm5vY2FjaGVfcGFnZXM6Cgpub2NhY2hlX3RhZ3M6CiAgICAtIGRhdGFfdGFibGUKICAgIC0gY2FsbG91dHMKICAgIC0gcGxhY2Vob2xkZXIK'
    ];

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['dest']);
        $dest = $opt['dest'] ?? '/tmp/apex-core';

        // Make directory
        $this->io->createBlankDir($dest);

        // Define root dirs
        $root_dirs = [
            'boot', 
            'docs', 
            'etc', 
            'public/themes', 
            'public/plugins', 
            'src/HttpControllers',
            'storage/logs', 
            'tests', 
            'views/html',
            'views/php',
            'views/themes'
        ];

        // Create root dirs
        foreach ($root_dirs as $dir) { 
            mkdir("$dest/$dir", 0755, true);
        }

        // Set base files
        $base_files = [
            'apex',
            'composer.json',
            'docker-compose.yml',
            'install_example.yml',
            'License.txt',
            'phpunit.xml',
            'Readme.md',
            'etc/Core',
            'boot/init',
            'boot/docker',
            'views/themes/default',
            'public/index.php',
            'public/.htaccess',
            'public/robots.txt',
            'public/themes/default',
            'src/HttpControllers/PublicSite.php',
            'views/html/index.html',
            'views/html/404.html',
            'views/html/500.html',
            'views/html/500_generic.html',
            'views/php/index.php'
        ];

        // Copy base files
        foreach ($base_files as $file) { 
            $source = SITE_PATH . '/' . $file;
            system("cp -R $source $dest/$file");
        }

        // Save template files
        file_put_contents("$dest/boot/container.php", base64_decode($this->templates['container.php']));
        file_put_contents("$dest/boot/routes.yml", base64_decode($this->templates['routes.yml']));
        file_put_contents("$dest/boot/site.yml", base64_decode($this->templates['site.yml']));
        file_put_contents("$dest/.env", base64_decode($this->templates['env']));

        // Create yaml files
        $this->createYamlFiles($dest);

        // Success
        $cli->success("Successfully compiled the core Apex package to $dest\r\n\r\n");
    }

    /**
     * Create yaml files
     */
    private function createYamlFiles(string $dest):void
    {

        // Get core version
        $pkg = $this->pkg_store->get('core');

        // Set config.yml yaml
        $config_yaml = [
            'packages' => [],
            'repos' => []
        ];

        // Core package
            $config_yaml['packages']['core'] = [
            'type' => 'package',
            'version' => $pkg->getVersion(),
            'author' => 'apex',
            'local_user' => '',
            'repo_alias' => 'apex',
            'installed_at' => time()
        ];

        // Repo entry
        $config_yaml['repos']['apex'] = [
            'host' => 'api.apexpl.io',
            'http_host' => 'code.apexpl.io',
            'svn_host' => 'svn.apexpl.io',
            'staging_host' => 'apexpl.dev',
            'name' => 'Apex Public Repository'
        ];

        // Save config.yml file
        file_put_contents("$dest/etc/.config.yml", Yaml::dump($config_yaml, 5));
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Compile Apex Core',
            usage: 'sys compile-core [--dest=]',
            description: "Never needs to be run, and is used by the maintainers of Apex to compile the base Github repository."
        );

        $help->addFlag('--dest', "Optional destination directory where to save Apex core to.  Defaults to the system's tmp directory.");
        $help->addExample('./apex sys compile-core');

        // Return
        return $help;
    }

}


