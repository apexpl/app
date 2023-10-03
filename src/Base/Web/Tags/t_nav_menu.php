<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\Svc\{App, Db};
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\App\Exceptions\ApexYamlException;
use Apex\App\Attr\Inject;
use redis;


/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_nav_menu implements TagInterface
{
    /**
     * Constructor
     */
public function __construct(
        private App $app,
        private Db $db,
        private redis $redis, 
        private Tags $tags,
        private array $allowed = []
    ) { 

    }

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Get menus
        $area = $this->app->getArea();
        $prefix_links = $this->app->getClient()->getPrefixMenuLinks();
        $base_domain = $e->getAttr('base_domain') ?? '';
        if (!$menus = $this->getArea($area)) { 
            return '';
        }

        // Get allowed menus
        if ($this->app->getArea() == 'admin') {
            $rows = $this->db->query("SELECT * FROM cms_menus_acl WHERE area = 'admin' AND uuid = %s", $this->app->getUuid());
            foreach ($rows as $row) {
                $alias = $row['alias'] == '' ? $row['parent'] : $row['parent'] . '.' . $row['alias'];
                $this->allowed[] = $alias;
            }
        }

        // Go through menus
        $html = '';
        $nav_num=1;
        foreach ($menus as $alias => $row) { 

            // Check if viewable
            if (!$this->isViewable($alias, $row)) 
                { continue; 
            }

            // Get sub-menu html
            $sub_html = '';
            $sub_menus = $row['menus'] ?? [];
                foreach ($sub_menus as $sub_alias => $srow) { 

                // Check if viewable
                if (!$this->isViewable($alias . '.' . $sub_alias, $srow)) 
                    { continue; 
                }

            // Get url
                $url = $prefix_links === false ? "/$alias/$sub_alias" : "/$area/$alias/$sub_alias";
                if ($srow['type'] == 'external') { 
                    $url = $srow['url'];
                } elseif ($base_domain != '') {
                    $url = "https://" . $base_domain . $url;
                }

                // Add to html
                $sub_html .= $this->tags->getSnippet('nav.menu', '', [
                    'url' => $url, 
                    'icon' => $srow['icon'], 
                    'name' => $srow['name']
                ]);
            }

            // Get uri link
            $url = match($row['type']) { 
                'parent' => '#', 
                'external' => $row['url'], 
                default => ($prefix_links === false ? "/$alias" : "/$area/$alias")
            }; 

            // Add base domain, if needed
            if ($row['type'] == 'internal' && $base_domain != '') {
                $url = "https://" . $base_domain . $url;
            }

            // Set variables
            $vars = [
                'url' => $url, 
                'icon' => $row['icon'] == '' ? '' : '<i class="' . $row['icon'] . '"></i>', 
                'name' => $row['name'], 
                'submenus' => $sub_html,
                'nav_num' => $nav_num
            ];

            // Add to html
            $tag_name = in_array($row['type'], ['internal', 'external']) ? 'nav.menu' : 'nav.' . $row['type'];
            $html .= $this->tags->getSnippet($tag_name, '', $vars);
            $nav_num++;
        }

        // Return
        return $html;
    }

    /**
     * Get area menus
     */
    public function getArea(string $area):?array
    {

        // Get YAML code
        if (!$yaml_code = $this->redis->get('cms:menus:' . $area)) { 
            return null;
        }

        // Load YAML file
        try {
            $menus = Yaml::parse($yaml_code);
        } catch (ParseException $e) { 
            throw new ApexYamlException("Unable to parse menu YAML code from redis for area '$area'.  Error: " . $e->getMessage());
        }

        // Return
        return $menus;
    }

    /**
     * Check whether or not menu is viewable
     */
    public function isViewable(string $alias, array $menu):bool
    {

        // Skip if public site, and login / no_login required
        if ($this->app->getArea() == 'public') { 
            if ($menu['require_login'] == 1 && $this->app->isAuth() === false) { 
                return false; 
            }
            if ($menu['require_nologin'] == 1 && $this->app->isAuth() === true) {
                return false;
            }
        }

        // Check acl
        if (count($this->allowed) > 0) {
            if (!in_array($alias, $this->allowed)) {
                return false;
            }
        }

        // Return
        return true;
    }

}


