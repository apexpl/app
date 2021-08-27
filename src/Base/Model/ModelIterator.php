<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use PDO;
use Apex\Db\Mapper\ToInstance;

/**
 * Mapper Iterable
 */
class ModelIterator implements \Iterator
{

    /**
     * Constructor
     */
    public function __construct(
        private \PDOStatement $stmt, 
        private string $class_name, 
        private int $position = 0
    ) { 
        $this->total = $this->stmt->rowCount();
    }

    /**
     * Rewind
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Current
     */
    public function current()
    {

        if (!$row = $this->stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $this->position)) { 
            return null;
        }

        // Return
        return ToInstance::map($this->class_name, $row);
    }

    /**
     * Key
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Next
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Valid
     */
    public function valid()
    {
        return $this->position >= $this->total ? false : true;
    }

}

