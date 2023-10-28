<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\App\Cli\Helpers\PackageHelper;
use Apex\Docs\DocsGenerator;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Generate package docs
 */
class GenDocs implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(PackageHelper::class)]
    private PackageHelper $pkg_helper;

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(DocsGenerator::class)]
    private DocsGenerator $generator;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get args
        $opt = $cli->getArgs(['subdir']);
        $subdir = $Opt['subdir'] ?? 'classes';

        // Get package
        if (!$pkg = $this->pkg_helper->get(($args[0] ?? ''))) { 
            return;
        } 
        $pkg_alias = $this->convert->case($args[0], 'title');

        // Ensure package is under version control
        if ($pkg->isLocal() === false && !is_dir(SITE_PATH . '/.apex/svn/' . $pkg->getAlias())) { 
            $cli->error("This package is not currently under version control, $pkg_alias");
            return;
        }

        // Set generation variables
        $source_dir = SITE_PATH . '/src/' . $pkg_alias;
        $dest_dir = SITE_PATH . '/docs/' . $pkg_alias . '/' . $subdir;
        $base_uri = '/' . $pkg->getSerial() . '/trunk/docs/classes/';
        $base_namespace = "App\\" . $pkg_alias . "\\";
        $this->io->createBlankDir($dest_dir);

        // Generate docs
        $this->generator->generateDirectory($source_dir, $dest_dir, $base_namespace, $base_uri, 'markdown', true);

        // Success
        $cli->send("Successfully generated documentation for the package $pkg_alias, which you may now view at /docs/$pkg_alias/$subdir/\n\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        // Start help
        $help = new CliHelpScreen(
            title: 'Generate Package Docs',
            usage: 'package docs gen-package <PKG_ALIAS> [--subdir <SUBDIR>]',
            description: 'Generates all necessary developer documentation for the specified package, and saves it within the /docs/ directory.'
        );


            // Return
        return $help;
    }

}




