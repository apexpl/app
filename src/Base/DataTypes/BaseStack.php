<?php
declare(strict_types = 1);

namespace Apex\App\Base\DataTypes;

use Apex\Db\Mapper\FromInstance;


/**
 * Base stack
 */
abstract class BaseStack
{


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
     * Push
     */
    public function push($value):void
    {

        // Enforce type constraint
        if (!$value instanceof static::$item_class) { 
            $class = is_object($value) ? $value::class : GetType($value);
            throw new \InvalidArgumentException("The class " . static::class . " only allows items of " . static::$item_class . " but received item of $class");
        }

        // Add to stack
        $this->items[] = $value;
    }

    /**
     * Pull
     */
    public function pull()
    {
        return count($this->items) > 0 ? array_shift($this->items) : null;
    }

    /**
     * Clear
     */
    public function clear():void
    {
        $this->items = [];
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

