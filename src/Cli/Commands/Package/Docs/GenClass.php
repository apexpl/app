<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Package\Docs;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\Docs\DocsGenerator;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Generate package docs
 */
class GenClass implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

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
        $class_name = $args[0] ?? '';
        $dest_dir = $args[1] ?? '';

        // Perform checks
        if ($class_name == '' || !class_exists($class_name)) {
            $cli->error("No class exists at $class_name");
            return;
        } else if ($dest_dir != '') {
            $cli->error("You did not specify a destination directory");
            return;
        }
        $dest_dir = SITE_PATH . '/' . trim($dest_dir, '/');

        // Set generation variables
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




