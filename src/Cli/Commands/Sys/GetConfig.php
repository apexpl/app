<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Interfaces\Opus\CliCommandInterface;
use Apex\App\Attr\Inject;
use redis;

/**
 * Set config
 */
class GetConfig implements CliCommandInterface
{

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Initialize
        $term = $args[0] ?? '';

        // Get config vars
        $keys = $this->redis->hkeys('config');
        if ($term != '') { 
            $keys = array_filter($keys, fn ($var) => str_contains($var, $term));
        }
        asort($keys);

        // Check if no results
        if (count($keys) == 0) { 
            $cli->send("No configuration variables found matching the term '$term'.\r\n\r\n");
            return;
        }

        // Create rows
        $rows = [['Name', 'Value']];
        foreach ($keys as $key) { 
            $value = $this->redis->hget('config', $key) ?? '';
            $rows[] = [$key, $value];
        }

        // Send table
        $cli->sendTable($rows);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Get Configuration Variables', 
            usage: 'sys get-config [<TERM>]',
            description: 'List the values of configuration variables that match the optional term.',
            params: [
                'term' => 'Optional term to search for.  If unspecified, will list all configuration variables.'
            ], 
            examples: [
                './apex sys get-config blog'
            ]
        );

        // Return
        return $help;
    }

}


