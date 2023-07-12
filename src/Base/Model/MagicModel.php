<?php
declare(strict_types = 1);

namespace Apex\App\Base\Model;


/**
 * Magic model
 */
abstract class MagicModel extends BaseModel
{

    /**
     * Get
     */
    public function __get(string $prop):mixed
    {
        //$method = $this->convert->case('get-' . $prop, 'camel');
        //if (method_exists($this, $method)) {
            //return $this->$method();
        //}
        return isset($this->$prop) ? $this->$prop : null;
    }

    /**
     * Set
     */
    public function __set(string $prop, mixed $value):void
    {
        $this->$prop = $value;
        $this->updates[$prop] = $value;
    }


}

