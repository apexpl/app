<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\Db;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;
use Apex\App\Attr\Inject;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_boxlist implements TagInterface
{

    #[Inject(Tags::class)]
    private Tags $tags;

    #[Inject(Db::class)]
    private Db $db;

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Check for alias attribute
        if ($alias = $e->getAttr('alias')) { 
            return $this->renderAlias($alias, $e);
        }

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

    /**
     * Render alias
     */
    public function renderAlias(string $alias, StackElement $e):string
    {

        // Go through items
        $item_html = '';
        $rows = $this->db->query("SELECT * FROM internal_boxlists WHERE alias = %s ORDER BY order_num", $alias);
        foreach ($rows as $row) { 
            $item_html .= $this->tags->getSnippet('boxlist.item', $row['description'], [
                'href' => '/' . ltrim($row['href'], '/'),
                'title' => $row['title']
            ]);
        }

        // Return
        return $this->tags->getSnippet('boxlist', '', ['items' => $item_html]);
    }


}


