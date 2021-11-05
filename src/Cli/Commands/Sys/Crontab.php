<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\{Db, Convert, Container};
use Apex\App\CLi\{Cli, CliHelpScreen};
use Apex\App\Base\Implementors;
use Apex\App\Interfaces\Opus\{CliCommandInterface, CrontabInterface};
use Symfony\Component\Process\Process;
use Apex\App\Attr\Inject;

/**
 * Execute crontab jobs
 */
class Crontab
{

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Implementors::class)]
    private Implementors $implementors;

    // Periods
    private array $periods = [
        's' => 'second',
        'i' => 'minute',
        'h' => 'hour',
        'd' => 'day',
        'w' => 'week',
        'm' => 'month',
        'q' => 'quarter',
        'y' => 'year'
    ];

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Process queue, if needed
        if (count($args) < 2) { 
            $this->processQueue($cli);
            $cli->send("Successfully processed all necessary crontab jobs.\r\n\r\n");
            return;
        }

        // Get class name
        $class_name = "App\\" . $this->convert->case($args[0], 'title') . "\\Opus\\Crontabs\\" . $this->convert->case($args[1], 'title');
        if (!class_exists($class_name)) { 
            $cli->error("Class does not exist at, $class_name");
            return;
        }

        // Load class
        $obj = $this->cntr->make($class_name);

        // Check for 'process' method
        if (!method_exists($obj, 'process')) { 
            $cli->error("No process() method exists within the class, $class_name");
            return;
        }

        // Process
        $obj->process();

        // Send message
        $cli->send("Successfully processed the crontab job at, $class_name\r\n\r\n");
    }

    /**
     * Execute crontab job
     */
    private function processQueue(Cli $cli):void
    {

        // Scan crontab classes
        $this->scanCrontabClasses();

        // Go through queue
        $queue = $this->db->getColumn("SELECT class_name FROM internal_tasks WHERE execute_time < now()");
        foreach ($queue as $class_name) {

            // Parse class name
            $parts = explode("\\", $class_name);
            if (count($parts) < 4) { 
                $this->db->query("DELETE FROM internal_tasks WHERE class_name = %s", $class_name);
                continue;
            }

            // Set args to process crontab job
            $args = [
                SITE_PATH . '/apex',
                'sys',
                'crontab',
                $parts[2],
                $parts[5]
            ];

            // Process
            $process = new Process($args);
            $process->setWorkingDirectory(SITE_PATH);
            $process->run();

            // Check for error
            if ($process->isSuccessful() !== true) {
                $this->addFailed($class_name);
                continue;
            }

            // Load class
            $obj = $this->cntr->make($class_name);

            // Check auto run
            $auto_run = $obj->auto_run ?? false;
            if ($auto_run !== true) { 
                $this->db->query("DELETE FROM internal_tasks WHERE class_name = %s", $class_name);
                continue;
            } elseif (!isset($obj->interval)) { 
                $this->db->query("DELETE FROM internal_tasks WHERE class_name = %s", $class_name);
                continue;
            } elseif (!$execute_time = $this->addInterval($obj->interval)) { 
                $this->db->query("DELETE FROM internal_tasks WHERE class_name = %s", $class_name);
                continue;
            }

            // Update database
            $this->db->query("UPDATE internal_tasks SET last_execute_time = now(), execute_time = %s WHERE class_name = %s", $execute_time, $class_name);
            $cli->send("Successfully executed the crontab job, $class_name\r\n\r\n");
        }

    }

    /**
     * Add failed
     */
    private function addFailed(string $class_name):void
    {

        // Update failed
        $this->db->query("UPDATE internal_tasks SET failed = failed + 1 WHERE class_name = %s", $class_name);
        if (!$row = $this->db->getRow("SELECT * FROM internal_tasks WHERE class_name = %s", $class_name)) { 
            return;
        }

        // Add one hour, if failed 5+ times
        if ($row['failed'] >= 5) {
            $execute_time = $this->db->addTime('hour', 1);
            $this->db->query("UPDATE internal_tasks SET execute_time = %s WHERE class_name = %s", $execute_time, $class_name);
        }

    }


    /**
     * Scan crontab classes
     */
    private function scanCrontabClasses():void
    {

        // Get classes
        $classes = $this->implementors->getClassNames(CrontabInterface::class);
        $existing = $this->db->getColumn("SELECT class_name FROM internal_tasks");

        // Go through classes
        foreach ($classes as $class_name) { 

            // Check if already exists
            if (in_array($class_name, $existing)) { 
                continue;
            } elseif (!class_exists($class_name)) { 
                continue;
            }

            // Load class
            $obj = $this->cntr->make($class_name);
            $auto_run = $obj->auto_run ?? false;
            if ($auto_run !== true) { 
                continue;
            } elseif (!isset($obj->interval)) { 
                continue;
            }

            // Add interval
            if (!$execute_time = $this->addInterval($obj->interval)) { 
                continue;
            }

            // Add to database
        $this->db->insert('internal_tasks', [
                'class_name' => $class_name,
                'data' => ''
            ]);

        }

    }

    /**
     * Add interval
     */
    private function addInterval(string $interval):?string
    {

        // Parse interval
        if (!preg_match("/^(\w)(\d+)/", $interval, $m)) { 
            return null;
        }

        // Get period
        $period = strtolower($m[1]);
        if (!isset($this->periods[$period])) { 
            return null;
        }
        $period = $this->periods[$period];

        // Add time
        $new_time = $this->db->addTime($period, (int) $m[2]);
        return $new_time;
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Execute Crontab Jobs',
            usage: 'sys crontab [<PKG_ALIAS> <ALIAS>]',
            description: 'Execute specific crontab job, or all pending crontab jobs.  This is also the crontab job that should be setup on your server and run every minute.'
        );

        $help->addParam('pkg_alias', 'Optional package alias specific crontab job you wish to execute resides within.');
        $help->addParam('alias', 'Optional alias of specific crontab job to execute.');
        $help->addExample('./apex sys crontab transaction get-rates');
        $help->addExample('./apex sys crontab');

        // Return
        return $help;
    }

}


