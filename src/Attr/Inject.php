<?php
declare(strict_types = 1);

namespace Apex\App\Attr;

use Attribute;

/**
 * Inject attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{

    /**
     * Constructor
     */
    public function __construct(string $class_name)
    {

    }

}

