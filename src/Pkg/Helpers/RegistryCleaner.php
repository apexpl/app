<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Svn\SvnFileConverter;

/**
 * Registry cleaner
 */
class RegistryCleaner
{

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $file_converter;

    // Properties
    private LocalPackage $pkg;
    private array $registry;
    private string $svn_dir;

    /**
     * Clearn registry
     */
    public function clean(LocalPackage $pkg):void
    {

        // Check for SVN directory
        if (!is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) {
            return;
        }

        // Get registry
        $this->registry = $pkg->getRegistry();
        $this->svn_dir = SITE_PATH . '/.apex/svn/' . $pkg->getAlias();
        $this->pkg = $pkg;

        // Go through views
        $views = $this->registry['views'] ?? [];
        foreach ($views as $view) {
            $this->checkView($view);
        }

        // Http Controllers
        $http_controllers = $this->registry['http_controllers'] ?? [];
        foreach ($http_controllers as $controller) {
            $this->checkHttpController($http_controller);
        }

        // External files
        $ext_files = $this->registry['ext_files'] ?? [];
        foreach ($ext_files as $file) {
            $this->checkExternalFile($file);
        }

    }

    /**
     * Check view
     */
    private function checkView(string $view):void
    {

        // Check files
        if ($this->checkFile('views/html' . $view . '.html', true) === true || $this->checkFile('views/php/' . $view . '.php', true)) {
                return;
            }

        // Remove from registry
        if (false === ($key = array_search($view, $this->registry['views']))) {
                return;
        }
        array_splice($this->registry['views'], $key, 1);
    }

    /**
     * Check http controller
     */
    private function checkHttpController(string $http_controller):void
    {

            // Check file
        $local_file = 'src/HttpControllers/' . $http_controller . '.php';
        if ($this->checkFile($local_file) === true) {
            return;
        }

        // Remove from registry
        if (false === (array_search($http_controller, $this->registry['http_controllers']))) {
            return;
        }
        array_splice($this->registry['http_controllers'], $key, 1);
    }

    /**
     * Check external file
     */
    private function checkExternalFile(string $file):void
    {

        // Check file
        if ($this->checkFile($file) === true) {
            return;
        } elseif (is_dir("$this->svn_dir/$file")) {
            return;
        } elseif (!is_dir(SITE_PATH . '/' . $file)) {
            return;
        }

        // Create parent directory, if needed
        $svn_file = "$this->svn_dir/ext/$file";
        if (!is_dir(dirname($svn_file))) {
            mkdir(dirname($svn_file), 0755, true);
        }

        // rename, and create symlink
        rename(SITE_APTH . '/' . $file, $svn_file);
        symlink($svn_file, SITE_PATH . '/' . $file);
    }

    /**
     * Check file
     */
    private function checkFile(string $local_file, bool $is_registry = false):bool
    {

        // Check svn file
        $svn_file = $this->file_converter->toSvn($pkg, $local_file, $is_registry);
        if (file_exists("$this->svn_dir/$svn_file")) {

            // Check for link
            if (!is_link(SITE_PATH . '/' . $local_file)) { 
                $this->io->removeFile("$this->svn_dir/$svn_file", true);
                return false;
            }
            return true;
        }

        // Check for local file
        if (file_exists(SITE_PATH . '/' . $local_file)) { 

            // Check for link
            if (is_link(SITE_PATH . '/' . $local_file)) {
                $this->io->removeFile(SITE_PATH . '/' . $local_file, true);
                return false;
            }

            // Create parent svn directory, if needed
            if (!is_dir(dirname("$this->svn_dir/$svn_file"))) {
                mkdir(dirname("$this->svn_dir/$svn_file"), 0755, true);
            }

            // Rename file, create link
            rename(SITE_PATH . '/' . $local_file, "$this->svn_dir/$svn_file");
            symlink("$this->svn_dir/$svn_file", SITE_PATH . '/' . $local_file);
            return true;
        }

        // Return false
        return false;
    }

}

