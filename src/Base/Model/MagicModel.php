<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;

/**
 * Magic model
 */
class MagicModel extends BaseModel
{

    /**
     * Call
     */
    public function __call(string $method, array $args):mixed
    {

        // Check if property exists
        if (!preg_match("/^(get|set)(.+)$/", $method, $m)) { 
            throw new \Exception("NO method exists at, $method");
        }
        $prop_name = $this->convert->case($m[2], 'lower');

        // Getter
        if ($m[1] == 'get') { 
            return isset($this->$prop_name) ? $this->$prop_name : null;
        } elseif ($m[1] == 'set') { 
            $this->$prop_name = $args[0];
        }

    }

    /**
     * Get
     */
    public function __get(string $prop):mixed
    {
        return isset($this->$prop) ? $this->$prop : null;
    }

    /**
     * Set
     */
    public function __set(string $prop, mixed $value):void
    {
        $parent->$prop = $value;
    }


}

