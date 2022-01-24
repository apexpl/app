<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

use Apex\Db\Mapper\FromInstance;

/**
 * Base Collection
 */
abstract class BaseCollection implements \ArrayAccess, \Iterator, \Countable, \jsonSerializable
{

    // Properties
    protected int $position = 0;
    protected array $items = [];

    /**
     * Constructor
     */
    public function __construct( 
        array $items = [] 
    ) {  

        // Ensure any items passed match item class
        foreach ($items as $value) { 
            if (!$value instanceof static::$item_class) { 
                $class = is_object($value) ? $value::class : GetType($value);
                throw new \InvalidArgumentException("The class " . static::class . " only allows items of " . static::$item_class . " but received item of $class");
            }
        }

        // Set properties
        $this->items = $items;
    }

    /**
     * Set offset
     */
    public function offsetSet(mixed $offset, mixed $value):void
    {

        // Enforce item_class
        if (!$value instanceof static::$item_class) { 
            $class = is_object($value) ? $value::class : GetType($value);
            throw new \InvalidArgumentException("The class " . static::class . " only allows items of " . static::$item_class . " but received item of $class");
        }

        // Add to items
        if (is_null($offset)) { 
            $this->items[] = $value;
        } else { 
            $this->items[$offset] = $value;
        }

    }

    /**
     * Offset exists
     */
    public function offsetExists(mixed $offset):bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Offset unset
     */
    public function offsetUnset(mixed $offset):void
    {
        unset($this->items[$offset]);
    }

    /**
     * Offset get
     */
    public function offsetGet(mixed $offset):mixed
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * Rewind
     */
    public function rewind():void
    {
        $this->position = 0;
    }

    /**
     * Current
     */
    public function current():mixed
    {
        return isset($this->items[$this->position]) ? $this->items[$this->position] : null;
    }

    /**
     * Key
     */
    public function key():mixed
    {
        return $this->position;
    }

    /**
     * Next
     */
    public function next():void
    {
        ++$this->position;
    }

    /**
     * Valid
     */
    public function valid():bool
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
            $json[] = FromInstance::map($item);
        }

        // Return
        return $json;
    }

}


