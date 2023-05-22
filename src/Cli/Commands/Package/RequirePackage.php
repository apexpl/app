<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\{Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Network\Stores\{PackagesStore, ReposStore};
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\App\Network\Svn\SvnInstall;
use Apex\App\Pkg\Helpers\Registry;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;

/**
 * Require package / dependency
 */
class RequirePackage implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(ReposStore::class  )]
    private ReposStore $repo_store;

    #[Inject(SvnInstall::class)]
    private SvnInstall $svn_install;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $opt = $cli->getArgs();
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $dep_alias = $args[1] ?? '';
        $version = $args[2] ?? '';
        $is_composer = $opt['composer'] ?? false;

        // Get package
        if (!$pkg = $this->pkg_store->get($pkg_alias)) { 
            $cli->error("Package does not exist, $pkg_alias");
            return;
        } elseif ($dep_alias == '') { 
            $cli->send("You did not specify a dependency to require.  Please see 'apex help package require' for details.");
            return;
        }

        // Require package
        if ($is_composer === true) { 
            $this->addComposerPackage($cli, $pkg_alias, $dep_alias, $version);
        } else { 
            $this->addApexPackage($cli, $pkg_alias, $dep_alias, $version);
        }

    }

    /**
     8 Add composer dependency
     */
    private function addComposerPackage(Cli $cli, string $pkg_alias, string $dep_alias, string $version = ''):void
    {

        // Get args
        $args = ['composer', 'require', $dep_alias];
        if ($version != '') { 
            $args[] = $version;
        }
        $args[] = '-n';

        // Execute process
        $process = new Process($args);
        $process->run(function ($type, $buffer) { 
            fputs(STDOUT, $buffer);
        });
        if (!$process->isSuccessful()) { 
            return;
        }

        // Get composer.json file
        if (!$json = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true)) { 
            $cli->error("Unable to read composer.json file.");
            return;
        } elseif (!isset($json['require'][$dep_alias])) { 
            $cli->send("Failed to install the $dep_alias Composer package.");
            return;
        }

        // Get version, if needed
        if ($version == '') { 
            $version = $json['require'][$dep_alias];
        }

        // Add to registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $registry->add('composer_require', $dep_alias, $version);

        // Success message
        $cli->send("\r\n\r\n");
        $cli->send("Successfully added the $dep_alias v$version Composer as a dependency of the $pkg_alias package.\r\n\r\n");
    }

    /**
     * Add Apex package
     */
    private function addApexPackage(Cli $cli, string $pkg_alias, string $dep_alias, string $version):void
    {

        // Install, if not already installed
        if (!$pkg = $this->pkg_store->get($dep_alias)) { 
            $repo = $this->repo_store->get('apex');

            if (!$pkg = $this->pkg_helper->checkPackageAccess($repo, $dep_alias, 'can_read')) { 
                return;
            }

            $this->svn_install->process($pkg->getSvnRepo(), $version); 
            $pkg = $this->pkg_store->get($dep_alias);
        }

        // Get version
        if ($version == '') { 
            $version = $pkg->getVersion();
        }

        // Add to registry
        $registry = $this->cntr->make(Registry::class, ['pkg_alias' => $pkg_alias]);
        $registry->add('apex_require', $dep_alias, $version);

        // Success message
        $cli->send("Successfully added dependency of $dep_alias v$version to the $pkg_alias package.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Require Package Dependency',
            usage: 'package require <PKG_ALIAS> <DEPENDENCY> [<VERSION>] [--composer]',
            description: "Installs a dependency (Composer or Apex), and registers it as a dependency with the package ensuring it's installed when the package is installed on other machines."
        );

        // Params
        $help->addParam('pkg_alias', 'The package alias to register the dependency to.');
        $help->addParam('dependency', 'The Composer / Apex package name of the dependency to install.');
        $help->addParam('version', 'Optional version of the dependency to install.');
        $help->addFlag('--composer', 'Add this flag if installing a Composer dependency.  If not present, Apex dependency will be assumed.');
        $help->addExample('./apex package require myshop apex/transaction');
        $help->addExample('./apex package require myshop guzzlehttp/http --composer');

        // Return
        return $help;
    }

}


