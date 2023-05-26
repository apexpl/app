<?php
declare(strict_types = 1);

namespace Apex\App\Network\Svn;

use Apex\Svc\{App, Container};
use Apex\App\Cli\Cli;
use Apex\App\Sys\Utils\{Io, SiteConfig, ScanClasses};
use Apex\App\Network\Svn\{SvnExport, SvnDependencies};
use Apex\App\Network\Sign\VerifyDownload;
use Apex\App\Network\Stores\PackagesStore;
use Apex\App\Base\Router\RouterConfig;
use Apex\App\Network\Models\LocalPackage;
use Apex\App\Pkg\Helpers\Migration;
use Apex\App\Pkg\Filesystem\Package\Installer;
use Apex\App\Pkg\Config\{EmailNotifications, DashboardItems};
use Apex\App\Exceptions\ApexSvnRepoException;
use Apex\App\Attr\Inject;

/**
 * Svn Install
 */
class SvnInstall
{

    #[Inject(Cli::class)]
    private Cli $cli;

    #[Inject(App::class)]
    private App $app;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(SvnExport::class)]
    private SvnExport $svn_export;

    #[Inject(SvnDependencies::class)]
    private SvnDependencies $dependencies;

    #[Inject(VerifyDownload::class)]
    private VerifyDownload $verifier;

    #[Inject(Installer::class)]
    private Installer $installer;

    #[Inject(PackagesStore::class)]
    private PackagesStore $pkg_store;

    #[Inject(Migration::class)]
    private Migration $migration;

    #[Inject(RouterConfig::class)]
    private RouterConfig $router_config;

    #[Inject(SiteConfig::class)]
    private SiteConfig $site_config;

    #[Inject(EmailNotifications::class)]
    private EmailNotifications $email_notifications;

    #[Inject(DashboardItems::class)]
    private DashboardItems $dashboard_items;

    #[Inject(ScanClasses::class)]
    private ScanClasses $scan_classes;

    // Properties
    private bool $update_composer = false;

    /**
     * Install
     */
    public function process(SvnRepo $svn, string $version = '', bool $dev = false, bool $noverify = false, bool $is_local_repo = false):void
    {

        // Export package
        $tmp_dir = $this->svn_export->process($svn, $version, $dev, $is_local_repo);
        $dir_name = $this->svn_export->svn_dir;
        $this->cli->send("Verifying digital signature... ");

        // Verify
        if ($noverify === true || $dev === true) { 
            $this->cli->send("Skipping verification checks, proceeding to install dependencies...\r\n");
        } else { 
            if (!$signed_by = $this->verifier->verify($svn, $dir_name, $tmp_dir)) { 
                $this->cli->error("Unable to install package, as verification failed.  Use --noverify flag to skip verification.");
                return;
            }
            $this->cli->send("done (signed by: $signed_by).\r\nInstalling dependences... \n");
        }

        // Install dependencies
        $this->dependencies->process($svn->getPackage()->getRepo(), $tmp_dir, $noverify, $is_local_repo);
        $this->cli->send("done.\r\nInstalling package... ");

        // Install
        $this->installer->install($svn->getPackage(), $tmp_dir, true);

        // Insert
        $pkg = $svn->getPackage();
        $pkg->setIsLocal(false);
        $pkg->setVersion($this->svn_export->version);
        $this->pkg_store->save($pkg);

        // Initial migration
        if ($this->app->isSlave() !== true) {
            $this->cli->send("Performing initial migration... ");
            $this->migration->install($pkg);
        }

        // Install composer dependencies
        $this->cli->send("done.\r\nFinalizing installation... ");
        $this->installComposerDependencies($pkg, $noverify);

        // Install registry
        $this->installRegistry($pkg);

        // Scan classes
        $this->scan_classes->scan();

        // Update composer, if needed
        if ($this->update_composer === true) { 
            shell_exec("composer update -n");
        }

        // Success
        $this->cli->send("done.\r\nInstallation complete.\r\n\r\n");
    }

    /**
     * Install dependencies
     */
    private function installComposerDependencies(LocalPackage $pkg, bool $no_verify):void
    {

        // Get composer dependencies
        $registry = $pkg->getRegistry();
        $dependencies = $registry['composer_require'] ?? [];
        if (count($dependencies) == 0) { 
            return;
        }

        // Load composer.json file
        $json = json_decode(file_get_contents(SITE_PATH . '/composer.json'), true);

        // Go through dependencies
        foreach ($dependencies as $package => $version) { 
            if (isset($json['require'][$package])) { 
                continue;
            }
            $json['require'][$package] = $version;
            $this->update_composer = true;
        }

        // Save composer.json file
        file_put_contents(SITE_PATH . '/composer.json', json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * Install registry
     */
    private function installRegistry(LocalPackage $pkg):void
    {

        // Get registry
        $registry = $pkg->getRegistry();
        $yaml = $pkg->getConfig();

        // Check for slave server
        if ($this->app->isSlave() !== true) {

            // Install e-mail notifications
            $this->email_notifications->install($yaml);

            // Install dashboard items
            $this->dashboard_items->install($pkg->getAlias());
        }

        // Go through routes
        $routes = $registry['routes'] ?? [];
        foreach ($routes as $route => $http_controller) { 
            // Ensure class exists
            if (!class_exists("\\App\\HttpControllers\\$http_controller")) { 
                continue;
            }
            $this->router_config->addRoute($route, $http_controller);
        }

        // Add themes
        $themes = $registry['themes'] ?? [];
        foreach ($themes as $path => $theme_alias) { 
            $this->site_config->addTheme($path, $theme_alias);
        }

        // Add user types
        $user_types = $registry['user_types'] ?? [];
        foreach ($user_types as $type => $vars) { 
            $this->site_config->addUserType($type, $vars['table'], $vars['class']);
        }

    }

}

