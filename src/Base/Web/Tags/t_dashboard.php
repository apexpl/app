<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\Db;
use App\Webapp\Dashboard;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;
use Apex\App\Interfaces\Opus\DashboardItemInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_dashboard implements TagInterface
{

    #[Inject(Tags::class)]
    private Tags $tags;

    #[Inject(Db::class)]
    private Db $db;

    #[Inject(Dashboard::class)]
    private Dashboard $dashboard;

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Get dashboard profile
        $profile = $this->dashboard->getProfile();

        // Top items
        $top_items = '';
        foreach ($profile->getTopItems() as $item) {
            $top_items .= $this->renderItem($item);
        }

        // Get right items
        $right_items = '';
        foreach ($profile->getRightItems() as $item) {
            $right_items .= $this->renderItem($item);
        }

        // Tab pages
        $tab_pages = '';
        foreach ($profile->getTabs() as $item) {
            $tab_pages .= $this->renderItem($item);
        }

        // Set replace array
        $replace = [
            'top_items' => $top_items,
            'right_items' => $right_items,
            'tab_pages' => $tab_pages
        ];

        // Return
        return $this->tags->getSnippet('dashboard', '', $replace);
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
                'href' => $row['href'],
                'title' => $row['title']
            ]);
        }

        // Return
        return $this->tags->getSnippet('boxlist', '', ['items' => $item_html]);
    }

    /**
     * Render item
     */
    private function renderItem(DashboardItemInterface $item):string
    {

        // Set attributes
        $attr = [
            'title' => $item->title,
            'divid' => $item->divid ?? '',
            'panel_class' => $item->panel_class ?? '',
            'contents' => $item->render()
        ];

        // Tab page
        if ($item->type == 'tab') {
            $html = "<s:tab_page name=\"" . $item->title . "\">$attr[contents]</s:tab_page>\n";
        } else { 
            $tag_name = 'dashboard.' . $item->type . '_item';
            $html = $this->tags->getSnippet($tag_name, $attr['contents'], $attr);
        }

        // Return
        return $html;
    }

}


