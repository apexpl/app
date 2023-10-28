<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\App\Pkg\Helpers\PackageConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * GPT - Hashes
 */
class GptHashes extends GptClient
{

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;


    /**
     * Generate
     */
    public function generate(string $pkg_alias, array $tables):array
    {

        // Load config
        $yaml = $this->pkg_config->load($pkg_alias);
        $hashes = $yaml['hashes'] ?? [];
        $new_hashes = [];

        // Go through tables
        foreach ($tables as $table) {

            // GO through columns, look for enum() column type
            $cols = $this->db->getColumnDetails($table);
            foreach ($cols as $col_name => $vars) {

                // Check for enum()
                if (!str_starts_with($vars['type'], 'enum(')) {
                    continue;
                }
                $options = rtrim(ltrim($vars['type'], 'enum('), ')');

                // Create hash vars
                $hash_vars = [];
                foreach (explode(",", $options) as $var_name) {
                    $var_name = trim($var_name, "'");
                    $hash_vars[$var_name] = $this->convert->case($var_name, 'phrase');
                }

                // Add to hashes
                $hash_alias = str_replace(str_replace('-', '_', $pkg_alias) . '_', '', $table) . '_' . strtolower($col_name);
                $hashes[$hash_alias] = $hash_vars;
                $new_hashes[] = $hash_alias;
                echo "Creating hash '$hash_alias'... done.\n";
            }
        }
        $yaml['hashes'] = $hashes;

        // Save Yml file
        if (count($yaml['hashes']) > 0) {
            $filename = SITE_PATH . '/etc/' . $this->convert->case($pkg_alias, 'title') . '/package.yml';
            file_put_contents($filename, Yaml::dump($yaml, 6));
        }

        // Scan package.yml file
        $this->pkg_config->install($pkg_alias);

        // Return
        return $new_hashes;
    }

}


