<?php
declare(strict_types = 1);

namespace Apex\App\Base;

use redis;

/**
 * Implementors
 */
class Implementors
{

    #[Inject(redis::class)]
    private redis $redis;

    /**
     * Get class names
     */
    public function getClassNames(string $interface_name):array
    {

        // Check redis
        if (!$classes = $this->redis->smembers('config:interfaces:' . $interface_name)) { 
            $classes = [];
        }

        // Return
        return $classes;
    }

    /**
     * Get property value of all classes
     */
    public function getPropertyValues(string $interface_name, string $property_name):array
    {

        // Get classes
        $classes = $this->getClassNames($interface_name);

        // Go through classes
        $values = [];
        foreach ($classes as $class_name) { 

            // Check class exists
            if (!class_exists($class_name)) { 
                continue;
            }
            $obj = new \ReflectionClass($class_name);

        // Check property exists
        if (!$obj->hasProperty($property_name)) { 
                continue;
            }
            $values[$class_name] = $obj->getProperty($property_name)->getDefaultValue();
        }

        // Return
        return $values;
    }

    /**
     * Create select options
     */
    public function createSelectOptions(string $interface_name, string $property_name, string $selected = ''):string
    {

        // Get values
        $values = $this->getPropertyValues($interface_name, $property_name);

        // Create options
        $html = '';
        foreach ($values as $value => $name) {
            $chk = $value == $selected ? 'selected="selected"' : '';
            $html .= "<option value=\"$value\" $chk>$name</option>";
        }

        // Return
        return $html;
    }

}



