<?php
declare(strict_types = 1);

namespace Apex\Svc;

/**
 * Queue
 */
class Queue
{

    /**
     * Add
     */
    public function add(string $class_name, array $params = []):void
    {

        // Add to database
        $this->db->insert('internal_tasks', [
            'class_name' => $class_name,
            'data' => json_encode($data)
        ]);

    }

}

