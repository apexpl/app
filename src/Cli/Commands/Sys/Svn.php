<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;

/**
 * SVN
 */
class Svn implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        global $argv;
        $args = $argv;
        array_splice($args, 0, 1);
        if ($args[0] == 'svn') { 
            array_shift($args);
        }

        // Get package
        $pkg_alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $svn_dir = SITE_PATH . '/.apex/svn/' . $pkg_alias;
        if (!is_dir($svn_dir)) { 
            $cli->error("No local SVN working directory exists at, $svn_dir");
            return;
        }
        $args[0] = 'svn';

        // Run process
        $process = new Process($args);
        $process->setWorkingDirectory($svn_dir);
        $process->run(function ($type, $buffer) { 
            fputs(STDOUT, $buffer);
        });
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'SVN Client',
            usage: 'svn <PKG_ALIAS> <ARG1> <ARG2> <ARG3>...',
            description: 'Runs the SVN client within the local working copy of the specified package.  This allows you to call the SVN with any desired commands such as diff, log, info, update, merge, et al.'
        );
        $help->addParam('pkg_alias', 'The package alias of the local SVN working directory to wun SVN in.');

        // Return
        return $help;
    }

}


