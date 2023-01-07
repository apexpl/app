<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Image;

use Apex\App\Cli\{Cli, CliHelpScreen};

/**
 * Help
 */
class Help
{

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Installation Image Commands',
            usage: 'image <SUB_COMMAND> [OPTIONS]',
            description: 'Create and manage installation images which can be published to the repository, and used during installation for quick deployment of fully configured systems.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Set commands
        $help->addParam('create', 'Create new installation image.');
        $help->addParam('delete', 'Delete installation image off repository.');
        $help->addParam('list', 'List all installation images you have previously published.');
        $help->addParam('publish', 'Publish installation image to repository.');

        // Return
        return $help;
    }

}

