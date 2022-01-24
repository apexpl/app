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
        return isset($this->$prop) ? $this->$prop : null;
    }

    /**
     * Set
     */
    public function __set(string $prop, mixed $value):void
    {
        $this->$prop = $value;
    }


}

