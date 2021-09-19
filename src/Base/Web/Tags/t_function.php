<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\{Container, Convert};
use Apex\App\Base\Web\Components;
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

    #[Inject(Components::class)]
    private Components $components;

    // Global  functions
    private array $core_functions = [
        'DisplayForm' => \Apex\App\Base\Web\Render\Form::class,
        'DisplayTable' => \Apex\App\Base\Web\Render\DataTable::class,
        'DisplayAutoComplete' => \Apex\App\Base\Web\Render\AutoComplete::class,
        'DisplayTabControl' => \Apex\App\Base\Web\Render\TabControl::class,
        'DisplayGraph' => \Apex\App\Base\Web\Render\Graph::class,
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
        $chk_alias = $this->convert->case($alias, 'title');

        // Check for core function
        if (isset($this->core_functions[$chk_alias])) { 
            $obj = $this->cntr->make($this->core_functions[$chk_alias]);
            return $obj->render($e);
        }

        // Load HTML function
        if (!$obj = $this->components->load('HtmlFunction', $alias, $e->getAttrAll())) { 
            return "<B>ERROR:</b> No HTML function exists with the alias, $alias";
        }

        // Get HTML filename
        $ref_obj = new \ReflectionClass($obj::class);
        $html_file = preg_replace("/\.php$/", '.html', $ref_obj->getFilename());

        // Get HTML code
        $html = '';
        if (file_exists($html_file)) { 
            $html = file_get_contents($html_file);
        }

        // Render function
        $html = $obj->render($html, $e);

        // Return
        return $html;
    }

}


