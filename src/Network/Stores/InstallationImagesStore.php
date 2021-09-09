<?php
declare(strict_types = 1);

namespace Apex\App\Network\Stores;

use Apex\Svc\Container;
use Apex\App\Network\Stores\AbstractStore;
use Apex\App\Network\Models\InstallationImage;
use Apex\App\Sys\Utils\Io;
use Symfony\Component\Yaml\Yaml;

/**
 * Installation image store
 */
class InstallationImagesStore extends AbstractStore
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Io::class)]
    private Io $io;

    /**
     * List
     */
    public function list():array
    {

        // Check for /images/ directory
        if (!is_dir(SITE_PATH . '/images/')) { 
            return [];
        }

        // Go through subdirs
        $images = [];
        $files = scandir(SITE_PATH . '/images');
        foreach ($files as $file) { 

            // Skip, if needed
            if (in_array($file, ['.', '..']) || !is_dir(SITE_PATH . "/images/$file")) { 
                continue;
            } elseif (!file_exists(SITE_PATH . "/images/$alias/config.yml")) { 
                continue;
            }
            $images[] = $file;
        }

        // Return
        return $images;
    }

    /**
     * Get installation image
     */
    public function get(string $alias):?InstallationImage
    {

        // Check if file exists
        $yaml_file = SITE_PATH . '/images/' . $alias . '/config.yml';
        if (!file_exists($yaml_file)) { 
            return null;
        }

        // Load Yaml file
        $yaml = $this->loadYamlFile($yaml_file);
        $general = $yaml['general'] ?? [];

        // Make installation image instance
        $image = $this->cntr->make(InstallationImage::class, [
            'alias' => $alias,
            'name' => $general['name'],
            'version' => (string) $general['version'],
            'access' => $general['access'],
            'description' => $general['description'] ?? '',
            'packages' => $yaml['packages'] ?? [],
            'config' => $yaml['config'] ?? []
        ]);

        // Return
        return $image;
    }

    /**
     * Delete
     */
    public function delete(string $alias):bool
    {

        // Check image exists
        if (!file_exists(SITE_PATH . "/images/$alias/config.yml")) { 
            return false;
        }

        // Delete directory
        $this->io->removeDir(SITE_PATH . "/images/$alias");
        return true;
    }

    /**
     * Save
     */
    public function save(InstallationImage $image):void
    {
        $yaml = Yaml::dump($image->toArray(), 5);
        file_put_contents(SITE_PATH . '/images/' . $image->getAlias() . '/config.yml', $yaml);
    }

}


