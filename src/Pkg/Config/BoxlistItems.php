<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\Db;
use Apex\App\Attr\Inject;

/**
 * Bostlist Items
 */
class BoxlistItems
{

    #[Inject(Db::class)]
    private Db $db;

    /**
     * Constructor
     */
    public function __construct(
        private string $pkg_alias
    ) { 

    }

    /**
     8 Install
     */
    public function install(array $yaml):void
    {

        // Initialize
        $items = $yaml['boxlist_items'] ?? [];
        $reorder = [];

        // GO through items
        foreach ($items as $vars) { 

            // Initialize
            $position = $vars['position'] ?? 'bottom';
            $reorder[$vars['alias']] = 1;

            // Check if exists
            if ($row = $this->db->getRow("SELECT * FROM internal_boxlists WHERE alias = %s AND href = %s", $vars['alias'], $vars['href'])) { 

                // Update
                $this->db->update('internal_boxlists', [
                    'position' => $position,
                    'title' => $vars['title'],
                    'description' => $vars['description']
                ], 'id = %i', $row['id']);
                continue;
            }

            // Add to database
            $this->db->insert('internal_boxlists', [
                'package' => $this->pkg_alias,
                'alias' => $vars['alias'],
                'position' => $position,
                'href' => $vars['href'],
                'title' => $vars['title'],
                'description' => $vars['description']
            ]);

        }

        // Reorder needed boxlist items
        foreach (array_keys($reorder) as $alias) { 
            $this->reorderBoxlistItems($alias);
        }

    }

    /**
     * Reorder boxlist items
     */
    private function reorderBoxlistItems(string $alias):void
    {

        // Get hrefs
        $positions = $this->db->getHash("SELECT href,position FROM internal_boxlists WHERE alias = %s ORDER BY id", $alias);
        $hrefs = [];

        // GO through positions
        foreach ($positions as $href => $position) { 

            // Check before / after
            if (preg_match("/^(before|after)\s(.+)$/i", $position, $m)) { 

                if (false !== ($key = array_search($href, $hrefs))) { 
                    if (strtolower($m[1]) == 'after') { $key++; }
                    array_splice($hrefs, $key, 0, $href);
                } else { 
                    $position = 'bottom';
                }
            }

            // Top / bottom
            if ($position == 'top') { 
                array_unshift($hrefs, $href);
            } else { 
                $hrefs[] = $href;
            }
        }

        // Reorder items
        $order_num = 1;
        foreach ($hrefs as $href) { 
            $this->db->query("UPDATE internal_boxlists SET order_num = %i WHERE alias = %s AND href = %s", $order_num, $alias, $href);
            $order_num++;
        }
    }

    /**
     * Remove
     */
    public function remove(array $yaml):void
    {
        $this->db->query("DELETE FROM internal_boxlists WHERE package = %s OR alias LIKE %ls", $this->pkg_alias, $this->pkg_alias . '.');
    }

}


