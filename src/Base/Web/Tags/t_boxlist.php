<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_boxlist implements TagInterface
{

    #[Inject(Tags::class)]
    private Tags $tags;

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Get children
        $items = $e->getChildren('item');

        // Go through items
        $item_html = '';
        foreach ($items as $item) { 
            $item_html .= $this->tags->getSnippet('boxlist.item', $item->getBody(), $item->getAttrAll());
        }

        // Return
        return $this->tags->getSnippet('boxlist', '', ['items' => $item_html]);
    }

}


