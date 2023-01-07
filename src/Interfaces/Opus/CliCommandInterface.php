<?php

namespace Apex\App\Interfaces\Opus;

use Apex\App\Cli\{Cli, CliHelpScreen};

/**
 * Cli Command Interface
 */
interface CliCommandInterface
{

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void;

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen;

}


