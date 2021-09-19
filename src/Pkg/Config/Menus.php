<?php
declare(strict_types = 1);

namespace Apex\App\Pkg\Config;

use Apex\Svc\{Db, Debugger};
use Apex\App\Exceptions\ApexInvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use redis;

/**
 * Package config - Menus
 */
class Menus
{

    /**
     * Constructor
     */
    public function __construct(
        private string $pkg_alias, 
        private Db $db, 
        private redis $redis, 
        private ?Debugger $debugger = null
    ) { 

    }

    /**
     * Install
     */
    public function install(array $yaml):void
    {

        // Check for cms_menus table
        $this->db->clearCache();
        if (!$this->db->checkTable('cms_menus')) { 
            return;
        }

        // Initialize
        $menus = $yaml['menus'] ?? [];
        list($done, $reorder) = [[], []];
        $this->debugger?->add(2, "Starting install of menus on package $this->pkg_alias");

        // GO through menus
        foreach ($menus as $vars) { 

            // Add or update menu
            $this->addMenu($vars);

            // Add to done array
            $parent = $vars['parent'] ?? '';
            $done[] = implode(":", [$vars['area'], $parent, $vars['alias']]);
            $reorder[$vars['area'] . ':' . $parent] = 1;

            // Go through sub-menus
            $submenus = $vars['menus'] ?? [];
            $position = 'top';
            foreach ($submenus as $sub_alias => $sub_name) { 

                // Set sub_vars
                $sub_vars = is_array($sub_name) ? $sub_name : ['name' => $sub_name];
                $sub_vars['alias'] = $sub_alias;
                $sub_vars['area'] = $vars['area'];
                $sub_vars['parent'] = $vars['alias'];
                $sub_vars['position'] = $position;

                // Add menu
                $this->addMenu($sub_vars);
                $position = 'after ' . $sub_alias;

                // Add to done
                $done[] = implode(":", [$vars['area'], $vars['alias'], $sub_alias]);
                $reorder[$vars['area'] . ':' . $vars['alias']] = 1;
            }
        }

        // Delete needed menus
        $rows = $this->db->query("SELECT * FROM cms_menus WHERE package = %s ORDER BY id", $this->pkg_alias);
        foreach ($rows as $row) { 
            $chk = implode(":", [$row['area'], $row['parent'], $row['alias']]);
            if (in_array($chk, $done)) {
                continue;
            }

            // Delete
            $this->db->query("DELETE FROM cms_menus WHERE id = %i", $row['id']);
            $reorder[$row['area'] . ':' . $row['parent']] = 1;
        }

        // Refresh ordering of necessary menus
        foreach (array_keys($reorder) as $var) { 
            list($area, $parent) = explode(':', $var, 2);
            $this->refreshMenuOrder($area, $parent);
        }

        // Sync with redis
        $this->syncRedis();
        $this->debugger?->add(2, "Completed install of menus on package $this->pkg_alias");
    }

    /**
     * Add or update menu
     */
    private function addMenu(array $vars):void
    {

        // Validate
        $vars = $this->validate($vars);

        // Add or update menu as needed
        if ($menu_id = $this->db->getField("SELECT id FROM cms_menus WHERE package = %s AND area = %s AND parent = %s AND alias = %s", $vars['package'], $vars['area'], $vars['parent'], $vars['alias'])) { 
            $this->db->update('cms_menus', $vars, "id = %i", $menu_id);
        } else { 
            $this->db->insert('cms_menus', $vars);
        }

    }

    /**
     * Validate
     */
    private function validate(array $vars):array
    {

        // Initial checks
        if ((!isset($vars['area'])) || (!isset($vars['alias']))) { 
            throw new ApexInvalidArgumentException("Unable to add menu as either the required 'area' or 'alias' fields are missing.  Please correct this within the package's package.yml file.");
        } elseif (preg_match("/[\W\s]/", $vars['area']) || preg_match("/[\W\s]/", $vars['alias'])) { 
            throw new ApexInvalidArgumentException("Unable to add menu as either the 'area' or 'alias' fields contain spaces or special characters which are disallowed.");
        }

        // Format variables
        $vars['package'] = $this->pkg_alias;
        $vars['area'] = strtolower($vars['area']);
        $vars['alias'] = strtolower($vars['alias']);
        $vars['parent'] = isset($vars['parent']) ? strtolower($vars['parent']) : '';
        unset($vars['menus']);
        if (!isset($vars['name'])) { 
            $vars['name'] = ucwords(str_replace('_', ' ', $vars['alias']));
        }

        // Check for extranneous vars
        $allowed = ['package', 'require_login', 'require_nologin', 'area', 'type', 'icon', 'parent', 'alias', 'name', 'position', 'url'];
        foreach ($vars as $key => $value) { 

            if (!in_array($key, $allowed)) { 
                throw new ApexInvalidArgumentException("The menu with alias $vars[alias] has a $key variable within package.yml configuration which is not supported.");
            }

        }

        // Check booleans
        if (isset($vars['require_login']) && !in_array($vars['require_login'], ['1', '0', 'true', 'false'])) { 
            throw new ApexInvalidArgumentException("The menu with alias $vars[alias] has an invalid 'require_login' value of $vars[require_login]");
        } elseif (isset($vars['require_nologin']) && !in_array($vars['require_nologin'], ['1', '0', 'true', 'false'])) { 
            throw new ApexInvalidArgumentException("The menu with alias $vars[alias] has an invalid 'require_nologin' value of $vars[require_login]");
        }

        // Return
        return $vars;
    }

    /**
 * Refresh menu order
     */
    private function refreshMenuOrder(string $area, string $parent):void
    {

        // Initialize
        list($menus, $headers, $recheck) = [[], [], []];

        // Go through menus
        $rows = $this->db->query("SELECT * FROM cms_menus WHERE area = %s AND parent = %s ORDER BY id", $area, $parent);
        foreach ($rows as $row) { 

            // Place menu appropriately
            if ($row['position'] == 'top') { 
                array_unshift($menus, $row['alias']);
                if ($row['type'] == 'header') { 
                    array_unshift($headers, $row['alias']);
                }
                continue;

        // Check for botton, if not before / after
            } elseif (!preg_match("/^(before|after)\s(.+)/", strtolower($row['position']), $match)) {
                $menus[] = $row['alias'];
                if ($row['type'] == 'header') { 
                    $headers[] = $row['alias'];
                }
                continue;
            }

            // Check for after header
            list($dir, $search) = [$match[1], $match[2]];
            if ($dir == 'after' && ($key = array_search($search, $headers)) !== false) { 

                // Check for next header
                if (!isset($headers[++$key])) { 
                    $menus[] = $row['alias'];
                    if ($row['type'] == 'header') { 
                        $headers[] = $row['alias'];
                    }
                    continue;
                }
                $search = $headers[$key];
                $dir = 'before';
            }

            // Search for menu
            if (($key = array_search($match[2], $menus)) !== false) { 

                if ($match[1] == 'after') { 
                    $key++; 
                }
                array_splice($menus, $key, 0, $row['alias']);
            } else { 
                $recheck[$row['alias']] = $row['position'];
            }
        }

        // Recheck leftover menus
        foreach ($recheck as $alias => $position) { 
            list($direction, $sibling) = explode(' ', $position, 2);
            if ($key = array_search($sibling, $menus)) { 
                if ($direction == 'after') {
                    $key++; 
                }
                array_splice($menus, $key, 0, $alias);
            } else { 
                $menus[] = $alias;
            }
        }

        // Update ordering of menus
        $order_num = 1;
        foreach ($menus as $alias) { 
            $this->db->query("UPDATE cms_menus SET order_num = %i WHERE area = %s AND parent = %s AND alias = %s", $order_num, $area, $parent, $alias);
            $order_num++;
        }
    }

    /**
     * Sync redis 
     */
    private function syncRedis():void
    {

        // Delete existing from redis
        $keys = $this->redis->keys('cms:menus:*');
        foreach ($keys as $key) { 
            $this->redis->del($key);
        }

        // Go through each area
        $areas = $this->db->getColumn("SELECT DISTINCT area FROM cms_menus");
        foreach ($areas as $area) { 

            // Generate YAML code
            $yaml = $this->getAreaYaml($area);

            // Save to redis
            $this->redis->set('cms:menus:' . $area, $yaml);
        }
    }

    /**
     * Get YAML menu code for specific area.
     */
    private function getAreaYaml(string $area):string
    {

        // Go through parent menus
        $menus = [];
        $rows = $this->db->query("SELECT * FROM cms_menus WHERE area = %s AND is_active = 1 AND parent = '' ORDER BY order_num", $area);
        foreach ($rows as $row) { 

            // Set vars
            $vars = [
                'type' => $row['type'], 
                'icon' => $row['icon'],  
                'require_login' => (int) $row['require_login'], 
                'require_nologin' => (int) $row['require_nologin'], 
                'name' => $row['name'], 
                'url' => $row['url']
            ];

            // Add sub-menus, if needed
            $sub_rows = $this->db->getHash("SELECT alias,name FROM cms_menus WHERE area = %s AND parent = %s AND is_active = 1 ORDER BY order_num", $area, $row['alias']);
            foreach ($sub_rows as $alias => $name) { 
                if (!isset($vars['menus'])) {
                    $vars['menus'] = []; 
                }
                $vars['menus'][$alias] = $name;
            }

            // Add to menus
            $menus[$row['alias']] = $vars;
        }

        // Return
        return Yaml::dump($menus);
    }

    /**
     * Remove
     */
    public function remove(array $yaml):void
    {

        // Check for cms_menus table
        $this->db->clearCache();
        if (!$this->db->checkTable('cms_menus')) { 
            return;
        }

        // Go through menus
        $reorder = [];
        $rows = $this->db->query("SELECT * FROM cms_menus WHERE package = %s", $this->pkg_alias);
        foreach ($rows as $row) { 
            $var = $row['area'] . ':' . $row['parent'];
            $reorder[$var] = 1;
        }

        // Refresh ordering of necessary menus
        foreach (array_keys($reorder) as $var) { 
            list($area, $parent) = explode(':', $var, 2);
            $this->refreshMenuOrder($area, $parent);
        }

        // Sync with redis
        $this->syncRedis();
    }

}


