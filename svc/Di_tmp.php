<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\Container\Container;

/**
 * Wrapper class that provides access to all container methods statically, 
 * meant to help save from passing a container object from class to class, 
 * method to metohd.
 */
class Di
{

    // Container instantiation properties
        private static string $config_file = __DIR__ . '/../boot/container.php';
    private static bool $use_autowiring = true;
    private static bool $use_attributes = true;
    private static bool $use_annotations = false;

    // Container instance
    private static ?Container $instance = null;

    /**
     * Calls a method of the instance.
     */
    public static function __callstatic($method, $params) 
    {

        // Ensure we have an instance defined
        if (!self::$instance) {
            self::$instance = new Container(
                self::$config_file, 
                self::$use_autowiring,
                self::$use_attributes, 
                self::$use_annotations
            );
        }

        // Call method, and return 
        return self::$instance->$method(...$params);
    }

}

