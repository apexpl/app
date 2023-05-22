<?php
declare(strict_types = 1);

namespace Apex\App\Cli\Commands\Sys;

use Apex\Svc\{Db, Convert, Container};
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Base\Implementors;
use Apex\App\Sys\TaskModel;
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

        // Initialize
        $opt = $cli->getArgs(['task-id']);
        $task_id = (int) ($opt['task-id'] ?? 0);

        // Process queue, if needed
        if (count($args) < 2 && $task_id == 0) { 
            $this->processQueue($cli);
            $cli->send("Successfully processed all necessary crontab jobs.\r\n\r\n");
            return;
        }

        // Get class name
        if ($task_id > 0) { 

            if (!$task = $this->db->getIdObject(TaskModel::class, 'internal_tasks', $task_id)) {
                $cli->error("No task exists with the id# $task_id");
                exit(1);
            }
            $class_name = $task->class_name;

        } else {
            $class_name = "App\\" . $this->convert->case($args[0], 'title') . "\\Opus\\Crontabs\\" . $this->convert->case($args[1], 'title');
        }

        // Load class
        if (!class_exists($class_name)) { 
            $cli->error("Class does not exist at, $class_name");
            exit(1);
        }
        $obj = $this->cntr->make($class_name);

        // Check for 'process' method
        if (!method_exists($obj, 'process')) { 
            $cli->error("No process() method exists within the class, $class_name");
            exit(1);
        }

        // Process
        if ($task_id > 0) {
            $data = json_decode($task->data, true);
            $obj->process($task, $data);
        } else {
            $obj->process();
        }

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
        $rows = $this->db->query("SELECT * FROM internal_tasks WHERE execute_time < now() AND in_progress = %b", false);
        foreach ($rows as $row) {

            // Set task in progress
            $this->db->query("UPDATE internal_tasks SET in_progress = %b WHERE id = %i", true, $row['id']);

            // Set args to process crontab job
            $args = [
                SITE_PATH . '/apex',
                'sys',
                'crontab',
                '--task-id',
                $row['id']
            ];

            // Process
            $process = new Process($args);
            $process->setWorkingDirectory(SITE_PATH);
            $process->run();

            // Check for error
            if ($process->isSuccessful() !== true) {
                $this->addFailed($row);
                $cli->send("An error occured while processing the task at, $row[class_name]\r\n\r\n");
                $cli->send("ERROR: " . $process->getErrorOutput() . "\r\n\r\n");
                continue;
            }

            // Load class
            $obj = $this->cntr->make($row['class_name']);

            // Check if we should delete task
            $auto_run = $obj->auto_run ?? false;
            $delete_task = match(true) {
                (bool) $row['is_onetime'] => true,
                $auto_run === false ? true : false => true,
                (!isset($obj->interval)) ? true : false => true,
                default => false
            };

            // Add interval, if needed
            if ($delete_task === false && !$execute_time = $this->addInterval($obj->interval)) { 
                $delete_task = true;
            }

            // Delete, if needed
            if ($delete_task === true) {
                $this->db->query("DELETE FROM internal_tasks WHERE id = %i", $row['id']);
            } else {
                $this->db->query("UPDATE internal_tasks SET last_execute_time = now(), execute_time = %s, in_progress = %b WHERE id = %i", $execute_time, false, $row['id']);
            }
            $cli->send("Successfully executed the task at, $row[class_name]\r\n\r\n");
        }

    }

    /**
     * Add failed
     */
    private function addFailed(array $row):void
    {

        // Update failed
        $this->db->query("UPDATE internal_tasks SET failed = failed + 1, in_progress = %b WHERE id = %i", false, $row['id']);

        // Add one hour, if failed 5+ times
        if ($row['failed'] >= 5) {
            $execute_time = $this->db->addTime('hour', 1);
            $this->db->query("UPDATE internal_tasks SET execute_time = %s WHERE id = %i", $execute_time, $row['id']);
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


