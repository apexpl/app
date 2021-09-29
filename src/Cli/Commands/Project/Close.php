<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Project;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\Opus\CliCommandInterface;
use redis;

/**
 * Close porject
 */
class Close implements CliCommandInterface
{

    #[Inject(Io::class)]
    private Io $io;

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Checks
        if (!$info = $this->redis->hgetall('config:project')) {
            $cli->error("There is no project checked out on this system.");
            return;
        }

        // Confirm closure
        $cli->send("WARNING: This will remove the entire Apex installation directory from under version control.\r\n\r\n");
        if (!$cli->getConfirm("Are you sure you want to continue?")) {
            $cli->send("Ok, goodbye.\r\n\r\n");
            return;
        }

        // Delete .svn directory
        $this->io->removeDir(SITE_PATH . '/.svn');

        // Remove redis info
        $this->redis->del('config:project');

        // Send message
        $cli->send("Successfully closed the project, $info[pkg_alias] and it is no longer under version control.\r\n\r\n");
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Close Project',
            usage: 'project close',
            description: 'Closes the project, and removes the entire Apex installation directory from version control.'
        );
        $help->addExample('./apex project close');

        // Return
        return $help;
    }

}


