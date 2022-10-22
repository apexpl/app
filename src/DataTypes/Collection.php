<?php
declare(strict_types = 1);

namespace Apex\App\DataTypes;

use Apex\App\Base\DataTypes\BaseCollection;

/**
 * Generic collection data type
 */
class Collection extends BaseCollection
{

    /**
     * Item class name.  Only instances of this class will be allowed as items of this collection. 
     */
    protected static string $item_class = '';

    /**
     * Constructor
     */
    public function __construct(
        string $item_class,
        array $items = []
    ) {
        self::$item_class = $item_class;
        parent::__construct($items);
    }

}

