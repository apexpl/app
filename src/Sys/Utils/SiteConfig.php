<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Utils;

use Apex\Svc\App;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\App\Attr\Inject;

/**
 * Site config
 */
class SiteConfig
{

    #[Inject(App::class)]
    private App $app;

    /**
     * Add theme
     */
    public function addTheme(string $path, string $theme_alias):void
    {

        // Get yaml
        $yaml = $this->app->getRoutesConfig('site.yml', true);
        $themes = $yaml['themes'] ?? [];

        // Add theme
        $themes[$path] = $theme_alias;
        $yaml['themes'] = $themes;

        // Save YAML file
        $text = $this->generateText($yaml);
        file_put_contents(SITE_PATH . '/boot/site.yml', $text);
    }

    /**
     * Remove theme
     */
    public function removeTheme(string $path):void
    {

        // Load yaml file
        $yaml = $this->app->getRoutesConfig('site.yml', true);
        $themes = $yaml['themes'] ?? [];

        // Remove theme
        unset($themes[$path]);
        $yaml['themes'] = $themes;

        // Save yaml file
        $text = $this->generateText($yaml);
        file_put_contents(SITE_PATH . '/boot/site.yml', $text);
    }

    /**
     * Add user type
     */
    public function addUserType(string $type, string $table_name, string $class_name):void
    {

        // Load yaml file
        $yaml = $this->app->getRoutesConfig('site.yml', true);
        $types = $yaml['user_types'] ?? [];

        // Add user type
        $types[$type] = [
            'table' => $table_name,
            'class' => $class_name
        ];
        $yaml['user_types'] = $types;

        // Save file
        $text = $this->generateText($yaml);
        file_put_contents(SITE_PATH . '/boot/site.yml', $text);
    }

    /**
     * Remove user type
     */
    public function removeUserType(string $type):void
    {

        // Load yaml file
        $yaml = $this->app->getRoutesConfig('site.yml', true);
        $types = $yaml['user_types'] ?? [];

        // Remove type
        unset($types[$type]);
        $yaml['user_types'] = $types;

        // Save file
        $text = $this->generateText($yaml);
        file_put_contents(SITE_PATH . '/boot/site.yml', $yaml);
    }

    /**
     * Generate YAML text
     */
    public function generateText(array $yaml):string
    {

        // Generate text
        $text = "\n##########\n# Site Config\n#\n";        $text .= "# This file has been auto-generated, but you may modify as desired below.  Please refer to the developer \n";
        $text .= "# documentation for details on the entries within this file.\n##########\n\n";
        $text .= Yaml::dump($yaml, 6);

        // Return
        return $text;
    }

}


