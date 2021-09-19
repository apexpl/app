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
        'container.php' => 'PD9waHAKCnVzZSBBcGV4XFN2Y1xGaWxlc3lzdGVtOwp1c2UgQXBleFxEYlxJbnRlcmZhY2VzXERiSW50ZXJmYWNlOwp1c2UgQXBleFxDbHVzdGVyXEludGVyZmFjZXNcQnJva2VySW50ZXJmYWNlOwp1c2UgQXBleFxBcHBcSW50ZXJmYWNlc1xSb3V0ZXJJbnRlcmZhY2U7CnVzZSBQc3JcTG9nXExvZ2dlckludGVyZmFjZTsKdXNlIFBzclxDYWNoZVxDYWNoZUl0ZW1Qb29sSW50ZXJmYWNlOwp1c2UgUHNyXEh0dHBcQ2xpZW50XENsaWVudEludGVyZmFjZSBhcyBIdHRwQ2xpZW50SW50ZXJmYWNlOwp1c2UgQXBleFxDb250YWluZXJcSW50ZXJmYWNlc1xBcGV4Q29udGFpbmVySW50ZXJmYWNlOwp1c2UgTW9ub2xvZ1xMb2dnZXI7CnVzZSBNb25vbG9nXEhhbmRsZXJcU3RyZWFtSGFuZGxlcjsKCnJldHVybiBbCiAgICBEYkludGVyZmFjZTo6Y2xhc3MgPT4gXEFwZXhcRGJcRHJpdmVyc1xteVNRTFxteVNRTDo6Y2xhc3MsIAogICAgQ2FjaGVJdGVtUG9vbEludGVyZmFjZTo6Y2xhc3MgPT4gbnVsbCwgCiAgICBIdHRwQ2xpZW50SW50ZXJmYWNlOjpjbGFzcyA9PiBmdW5jdGlvbiAoKSB7IHJldHVybiBuZXcgXEd1enpsZUh0dHBcQ2xpZW50KFsndmVyaWZ5JyA9PiBmYWxzZV0pOyB9LCAKICAgIEJyb2tlckludGVyZmFjZTo6Y2xhc3MgPT4gQXBleFxDbHVzdGVyXEJyb2tlcnNcTG9jYWw6OmNsYXNzLCAKICAgIEZpbGVzeXN0ZW06OmNsYXNzID0+IFtTdG9yYWdlOjpjbGFzcywgWwogICAgICAgICdhZGFwdGVyJyA9PiAnbG9jYWwnLCAKICAgICAgICAnY3JlZGVudGlhbHMnID0+IFsnZGlyJyA9PiBfX0RJUl9fIC4gJy9zdG9yYWdlJ10KICAgIF1dLAogICAgUm91dGVySW50ZXJmYWNlOjpjbGFzcyA9PiBcQXBleFxBcHBcQmFzZVxSb3V0ZXJcUm91dGVyOjpjbGFzcywKCiAgICBMb2dnZXJJbnRlcmZhY2U6OmNsYXNzID0+IGZ1bmN0aW9uKCkgeyAKICAgICAgICAkbG9nZGlyID0gX19ESVJfXyAuICcvLi4vc3RvcmFnZS9sb2dzJzsKICAgICAgICByZXR1cm4gbmV3IExvZ2dlcignYXBwJywgWwogICAgICAgICAgICBuZXcgU3RyZWFtSGFuZGxlcigkbG9nZGlyIC4gJy9kZWJ1Zy5sb2cnLCBMb2dnZXI6OkRFQlVHKSwgCiAgICAgICAgICAgIG5ldyBTdHJlYW1IYW5kbGVyKCRsb2dkaXIgLiAnL2FwcC5sb2cnLCBMb2dnZXI6OklORk8pLCAKICAgICAgICAgICAgbmV3IFN0cmVhbUhhbmRsZXIoJGxvZ2RpciAuICcvZXJyb3IubG9nJywgTG9nZ2VyOjpFUlJPUikgCiAgICAgICAgXSk7CiAgICB9LCAKCiAgICBBcGV4Q29udGFpbmVySW50ZXJmYWNlOjpjbGFzcyA9PiBmdW5jdGlvbigpIHsgCiAgICAgICAgcmV0dXJuIG5ldyBcQXBleFxDb250YWluZXJcQ29udGFpbmVyKHVzZV9hdHRyaWJ1dGVzOiB0cnVlKTsKICAgIH0sIAoKICAgICdzeXJ1cy5jYWNoZV90dGwnID0+IDMwMCwgCiAgICAnY2x1c3Rlci50aW1lb3V0X3NlY29uZHMnID0+IDMsICAKICAgICdhcm1vci5jb29raWVfcHJlZml4JyA9PiAnYXJtb3JfJywgCiAgICAnYXJtb3IuY29va2llJyA9PiBbCiAgICAgICAgJ3BhdGgnID0+ICcvJywgCiAgICAgICAgJ2RvbWFpbicgPT4gJzEyNy4wLjAuMScsIAogICAgICAgICdzZWN1cmUnID0+IGZhbHNlLCAKICAgICAgICAnaHR0cG9ubHknID0+IGZhbHNlCiAgICAgICAgLy8nc2FtZXNpdGUnID0+ICdzdHJpY3QnCiAgICBdIApdOwoKCg==',
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


