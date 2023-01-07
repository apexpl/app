<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Helpers;

/**
 * Opus helper
 */
class OpusHelper
{

    /**
     * Parse filename
     */
    public function parseFilename(string $filename, string $prefix = 'src'):string
    {

        // Format filename
        if (!preg_match("/^$prefix\//", $filename)) { 
            $filename = $prefix . '/' . ltrim($filename, '/');
        }
        if (!preg_match("/\.php$/", $filename)) { 
            $filename .= '.php';
        }

        // Return
        return $filename;
    }

    /**
     * Path to namespace
     */
    public function pathToNamespace(string $filename, string $initial_nm = 'App'):string
    {

        // Trim excess
        $filename = preg_replace("/^src\//", "", trim($filename, '/'));
        $filename = preg_replace("/\.php$/", "", $filename);

        // Get names
        $parts = explode("/", $filename);
        $namespace = $initial_nm . "\\" . implode("\\", $parts);

        // Return
        return $namespace;
    }

}


