<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\Models;

/**
 * ViewInfo Model
 */
class ViewInfoModel
{

    /**
     * Constructor
     */
    public function __construct(
        public readonly string $pkg_alias,
        public readonly string $parent_namespace,
        public readonly string $alias,
        public readonly string $route,
        public readonly string $uri,
        public readonly string $layout_class,
        public array $components,
        public readonly string $dbtable,
        public readonly string $description,
        public readonly array $files
    ) {

    }

}

