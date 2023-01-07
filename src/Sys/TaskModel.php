<?php
declare(strict_types = 1);

namespace Apex\App\Sys;

use Apex\App\Base\Model\MagicModel;
use DateTime;

/**
 * TaskModel Model
 */
class TaskModel extends MagicModel
{

    /**
     * Database table
     *
     * @var string
     */
    protected static string $dbtable = 'internal_tasks';

    /**
     * Constructor
     */
    public function __construct(
        protected int $id, 
        protected bool $is_onetime = false, 
        protected int $failed = 0, 
        protected ?DateTime $execute_time = null, 
        protected ?DateTime $last_execute_time = null, 
        protected string $class_name = '', 
        protected ?string $data = null
    ) { 

    }

}

