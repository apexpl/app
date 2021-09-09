<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Filesystem\InstallationImage;

/**
 * Installation image compiler
 */
class Compiler
{

    /**
     * Compile
     */
    public function compile(string $alias):string
    {

        // Create zip file
        $zip_file = $this->io->createZipArchive(SITE_PATH . '/images/' . $alias);
        return $zip_file;

    }

}





