<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web;

use Apex\Svc\{Container, Convert};
use Apex\App\Attr\Inject;

/**
 * Components
 */
class Components
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    /**
     * Load
     */
    public function load(string $type, string $alias, array $params = []):?object
    {

        // Check alias
        if (!preg_match("/^(.+?)\.(.+)$/", $alias, $match)) { 
            return null;
        }

        // Convert case as needed
        $package = $this->convert->case($match[1], 'title');
        $alias = $this->convert->case($match[2], 'title');
        if ($type != 'Ajax') { 
            $type .= 's';
        }

        // Check for class
        $class_name = "\\App\\$package\\Opus\\" . ucwords($type) . "\\$alias";
        if (!class_exists($class_name)) { 
            return null;
        }

        // Instantiate and return
        $obj = $this->cntr->make($class_name, $params);
        return $obj;
    }

}

