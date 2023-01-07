<?php
declare(strict_types = 1);

namespace Apex\App\Attr;

use Attribute;

/**
 * Depreciated attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Depreciated
{

    /**
     * Constructor
     */
    public function __construct(
        public ?string $message = null,
        public ?float $bc_version = null
    ) {

    }

}

