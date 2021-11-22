<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\App\Sys\TaskModel;
use DateTime;

/**
 * Queue
 */
class Queue
{

    /**
     * Add
     */
    public function add(string $class_name, array $params = [], ?DateTime $execute_time = null):TaskModel
    {

        // Get insert vars
        $insert_vars = [
            'is_onetime' => true,
            'class_name' => $class_name,
            'data' => json_encode($params)
        ];

        // Add execute time to insert vars
        if ($execute_time !== null) {
            $insert_vars['execute_time'] = $execute_time->format('Y-m-d H:i:s');
        }

        // Add to database
        $task = TaskModel::insert($insert_vars);

        // Return
        return $task;
    }

    /**
     * List
     */
    public function list():?ModelIterator
    {
        return TaskModel::where('is_onetime = %b ORDER BY execute_time', true);
    }

    /**
     * Purge
     */
    public function purge():void
    {
        TaskModel::delete(['is_onetime' => true]);
    }



}

