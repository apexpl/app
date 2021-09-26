<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Create;

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
            title: 'Create Commands',
            usage: 'create <SUB_COMMAND> [OPTIONS]',
            description: 'Create various web UI components.'
        );
        $help->setParamsTitle('AVAILABLE COMMANDS');

        // Add available commands
        $help->addParam('ajax', 'Create new AJAX function.');
        $help->addParam('auto-complete', 'Create new auto-complete list.');
        $help->addParam('cli', 'Create new CLI command.');
        $help->addParam('crontab', 'Create new crontab job.');
        $help->addParam('dashboard-item', 'Create new dashboard item.');
        $help->addParam('form', 'Create new HTML form.');
        $help->addParam('graph', 'Create new graph');
        $help->addParam('html-function', 'Create new HTML tag function.');
        $help->addParam('html-tag', 'Create new HTML tag class allowing <s:tag> to work within templates.');
        $help->addParam('http-controller', 'Create new PSR-15 compliant HTTP controller.');
        $help->addParam('listener', 'Create new listener for event dispatcher.');
        $help->addParam('modal', 'Create new popup modal box.');
        $help->addParam('tab-control', 'Create new tab control.');
        $help->addParam('table', 'Create new data table.');
        $help->addParam('tab-page', 'Create new tab page within existing tab control.');
        $help->addParam('test', 'Create new unit test.');
        $help->addParam('view', 'Create new view.');

        // Examples
        $help->addExample('./apex create table my-shop orders');
        $help->addExample('./apex create http-controller myshop category');
        $help->addExample('./apex create view myshop admin/shop/orders');

        // Return
        return $help;
    }

}


