<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Helpers;

use Apex\App\Network\Models\LocalPackage;
use Apex\App\Network\Svn\SvnFileConverter;
use Apex\App\Sys\Utils\Io;
use Apex\App\Attr\Inject;

/**
 * Registry cleaner
 */
class RegistryCleaner
{

    #[Inject(SvnFileConverter::class)]
    private SvnFileConverter $file_converter;

    #[Inject(Io::class)]
    private Io $io;

    // Properties
    private LocalPackage $pkg;
    private array $registry;

    /**
     * Clearn registry
     */
    public function clean(LocalPackage $pkg):void
    {

        // Get registry
        $this->registry = $pkg->getRegistry();
        $this->pkg = $pkg;

        // Go through views
        $views = $this->registry['views'] ?? [];
        foreach ($views as $view) {
            $this->checkView($view);
        }

        // Http Controllers
        $http_controllers = $this->registry['http_controllers'] ?? [];
        foreach ($http_controllers as $controller) {
            $this->checkHttpController($controller);
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

        // Initialize 
        $html_file = SITE_PATH . '/views/html/' . $view . '/.html';
        $php_file = SITE_PATH . '/views/php/' . $view . '.php';

        // Check if files exist
        if (file_exists($html_file) || file_exists($php_file)) {
            return;
        }

        // Remove from registry
        if (false === ($key = array_search($view, $this->registry['views']))) {
                return;
        }
        array_splice($this->registry['views'], $key, 1);
        $this->modified = true;
    }

    /**
     * Check http controller
     */
    private function checkHttpController(string $http_controller):void
    {

            // Check file
        $local_file = SITE_PATH . '/src/HttpControllers/' . $http_controller . '.php';
        if (file_exists($local_file)) {
            return;
        }

        // Remove from registry
        if (false === ($key = array_search($http_controller, $this->registry['http_controllers']))) {
            return;
        }
        array_splice($this->registry['http_controllers'], $key, 1);
        $this->modified = true;
    }

    /**
     * Check external file
     */
    private function checkExternalFile(string $file):void
    {

        // Check if exists
        $filepath = SITE_PATH . '/' . rtrim($file, '/');
        if (file_exists($filepath) || is_dir($filepath)) {
            return;
        }

        // Remove from registry
        if (false === ($key = array_search($file, $this->registry['ext_files']))) {
            return;
        }
        array_splice($this->registry['ext_files'], $key, 1);
        $this->modified = true;
    }

}


