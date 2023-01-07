<?php
declare(strict_types = 1);

namespace Apex\App\Attr;

use Attribute;

/**
 * PathParam attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class PathParam
{

    /**
     * Constructor
     */
    public function __construct(...$param_names)
    {

    }

}

