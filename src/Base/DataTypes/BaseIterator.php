<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use Apex\Db\Mapper\FromInstance;

/**
 * Base iterator
 */
abstract class BaseIterator implements \Iterator, \Countable, \jsonSerializable
{

    // Properties
    protected int $position = 0;

    /**
     * Constructor
     */
    public function __construct( 
        protected array $items = [] 
    ) {  

        // Ensure any items passed match item class
        foreach ($this->items as $value) { 
            if (!$value instanceof static::$item_class) { 
                $class = is_object($value) ? $value::class : GetType($value);
                throw new \InvalidArgumentException("The class " . static::class . " only allows items of " . static::$item_class . " but received item of $class");
            }
        }

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
        return isset($this->items[$this->position]) ? $this->items[$this->position] : null;
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
        return $this->position >= count($this->items) ? false : true;
    }

    /**
     * Count
     */
    public function count():int
    {
        return count($this->items);
    }

    /**
     * JSON serialize
     */
    public function jsonSerialize()
    {

        // Initialize
        $json = [];
        foreach ($this->items as $item) { 
            $json[] = FromInstance::$item);
        }

        // Return
        return $json;
    }

}


