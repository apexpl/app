<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Stores\{PackagesStore, ReposStore};
use Apex\App\Network\Svn\SvnExport;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Pkg\Filesystem\Package\Installer;
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Fork package
 */
class Fork implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(ReposStore::class)]
    private ReposStore $repo_store;

    #[Inject(SvnExport::class)]
    private SvnExport $svn_export;

    #[Inject(Installer::class)]
    private Installer $installer;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs(['repo']);
        $from = $this->pkg_helper->getSerial(($args[0] ?? ''));
        $pkg_alias = $this->convert->case(($args[1] ?? ''), 'lower');
        $repo_alias = $opt['repo'] ?? 'apex';

        // Checks
        if ($pkg_alias == '' || !preg_match("/^[a-zA-Z0-9_-]+/", $pkg_alias)) { 
            $cli->error("Invalid package alias, $pkg_alias");
            return;
        } elseif ($pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package already exists on this machine with the alias, $pkg_alias");
            return;
        } elseif (!$repo = $this->repo_store->get($repo_alias)) { 
            $cli->error("Repository is not configured on this machine with the alias, $repo_alias");
            return;
        }

        // Check user has read access to package
        if (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $from, 'can_read')) { 
            $cli->error("You do not have read access to the package, $from");
            return;
        }
        $from_alias = $pkg->getAlias();

        // Export package
        $tmp_dir = $this->svn_export->process($pkg->getSvnRepo());

        // Create and install package
        $pkg = $this->cntr->make(LocalPackage::class, ['alias' => $pkg_alias]);
        $this->installer->install($pkg, $tmp_dir, true);

        // Replace package name if .php files
        $this->processDirectory($from_alias, $pkg_alias, 'src');
        $this->processDirectory($from_alias, $pkg_alias, 'etc');

        // Save package 
        $this->pkg_store->save($pkg);
        $title_alias = $pkg->getAliasTitle();

        // Success message
        $cli->sendHeader('Successfully Forked Package');
        $cli->send("Successfully created new package $title_alias from the package '$from' and the following directories are now available and version controlled:\r\n\r\n");
        $cli->send("    /etc/$title_alias\r\n");
        $cli->send("    /src/$title_alias\r\n");
        $cli->send("    /tests/$title_alias\r\n");
        $cli->send("    /docs/$title_alias\r\n");
    }

    /**
     * Process dir
     */
    private function processDirectory(string $from_alias, string $pkg_alias, string $dir_name):void
    {

        // Convert
        $from_alias = $this->convert->case($from_alias, 'title');
        $pkg_alias = $this->convert->case($pkg_alias, 'title');
        $dir_name = SITE_PATH . '/' . $dir_name . '/' . $pkg_alias;

        // Set replace
        $replace = [
            "namespace App\\" . $from_alias . "\\" => "namespace App\\" . $pkg_alias . "\\",
            "namespace App\\" . $from_alias . ";" => "namespace App\\" . $pkg_alias . ";",
            "use App\\" . $from_alias . "\\" => "use App\\" . $pkg_alias . "\\"
        ];

        // Get files
        $files = $this->io->parseDir($dir_name);
        foreach ($files as $file) { 

            // Check for .php extension
            if (!str_ends_with($file, '.php')) { 
                continue;
            }

        // Replace code
            $code = strtr(file_get_contents("$dir_name/$file"), $replace);
            file_put_contents("$dir_name/$file", $code);
        }

    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Fork Package',
            usage: 'package fork <FROM_PACKAGE> <PKG_ALIAS>',
            description: 'Fork an existing package from the repository.'
        );

        $help->addParam('from_package', "The package from the repository to fork.");
        $help->addParam('pkg_alias', "The package alias to create on the local machine.");
        $help->addExample('./apex package fork apex/users my-users');

    }

}

