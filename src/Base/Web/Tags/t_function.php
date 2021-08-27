<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\{Container, Convert};
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Interfaces\TagInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_function implements TagInterface
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Convert::class)]
    private Convert $convert;

    // Global  functions
    private array $core_functions = [
        'DisplayForm' => \Apex\Opus\Render\Form::class, 
            'DisplayTable' => \Apex\Opus\Render\DataTable::class
    ];


    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Check
        if (!$alias = $e->getAttr('alias')) { 
            return "<b>ERROR:</b> No 'alias' attribute exists within the function tag.";
        }
        $alias = $this->convert->case($alias, 'title');

        // Check for core function
        if (isset($this->core_functions[$alias])) { 
            $obj = $this->cntr->make($this->core_functions[$alias]);
            return $obj->render($e);
        }

        return "Not yet implemented";
    }

}


