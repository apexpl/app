<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Render;

use Apex\Svc\{Convert, View, Container};
use Apex\App\Base\Web\Components;
use Apex\Syrus\Parser\StackElement;
use Apex\App\Interfaces\Opus\TabControlInterface;
use redis;

/**
 * Render tab control
 */
class TabControl
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(View::class)]
    private View $view;

    #[Inject(redis::class)]
    private redis $redis;

    #[Inject(Components::class)]
    private Components $components;

    /**
     * Render
     */
    public function render(StackElement $e):string
    {

        // Perform checks
        if (!$alias = $e->getAttr('tab_control')) { 
            return "<b>ERROR:</b> No 'tabcontrol' attribute was defined in the 'function' tag.";
        } elseif (!$obj = $this->components->load('TabControl', $alias, ['attr' => $e->getAttrAll()])) { 
            return "<b>ERROR:</b> No tab control exists with the alias, $alias";
        }

        // Process tab control, if needed
        if (method_exists($obj, 'process')) { 
            $obj->process();
        }

        // Get tab pages
        $tab_pages = $this->getTabPages($obj, $alias, $e->getAttrAll());

        // Get tab dir
        list($pkg_alias, $tab_alias) = explode('.', $alias, 2);
        $tab_dir = SITE_PATH . '/src/' . $this->convert->case($pkg_alias, 'title') . '/Opus/TabControls/' . $this->convert->case($tab_alias, 'title');

        // Check / confirm tab pages, if necessary.
        if (method_exists($obj, 'checkTabPages')) { 
            $tab_pages = $obj->checkTabPages($tab_pages, $e->getAttrAll());
        }

        // Go through tab pages
        $tab_html = "<s:tab_control>\n";
        foreach ($tab_pages as $tab_page => $tab_name) { 

            // Get HTML file
            if (preg_match("/^(.+?):(.+)$/", $tab_page, $match)) { 
                $html_file = $match[2] . '/' . $this->convert->case($match[1], 'title') . '.html';
            } else { 
                $html_file = "$tab_dir/$tab_page.html";
            }

            // Check if .html file exists
            if (!file_exists($html_file)) { 
                continue;
            }

            // Get page HTML
            $page_html = file_get_contents($html_file);

            /// Add to tab html
            $tab_name = tr($tab_name);
            $tab_html .= "    <s:tab_page name=\"$tab_name\">\n\n$page_html\n\t</s:tab_page>\n\n";
        }

        // Return
        $tab_html .= "</s:tab_control>\n";
        return $this->view->renderBlock($tab_html);
    }


    /**
     * Get tab pages
     */
    private function getTabPages(TabControlInterface $obj, string $parent, array $attr = []):array
    { 

        // Return, if dashboard
        if ($parent == 'webapp.dashboard') { 
            return $obj->tab_pages;
        }
        list($package, $parent) = explode('.', $parent, 2);

        // Set variables
        $tab_pages = $obj->tab_pages ?? [];
        $pages = array_keys($tab_pages);

        // Go through child classes
        $child_classes = $this->redis->smembers('config:child_classes:' . $obj::class);
        foreach ($child_classes as $child_class) {

            // Load class
            if (!class_exists($child_class)) { 
                continue;
            }
            $child_obj = $this->cntr->make($child_class);

            // Get alias
            $ref_obj = new \ReflectionClass($child_class);
            $alias = $ref_obj->getShortName() . ':' . dirname($ref_obj->getFilename());

            // Get position and name
            $position = $child_obj->position ?? 'bottom';
            $tab_pages[$alias] = $child_obj->name ?? $this->convert->case($alias, 'phrase');

            // Check for process() method
            if (method_exists($child_obj, 'process')) { 
                $child_obj->process($attr);
            }

            // Check before / after position
            if (preg_match("/^(before|after) (.+)$/i", $position, $match)) { 

                if ($num = array_search($match[2], $pages)) { 
                    if ($match[1] == 'after') { $num++; }
                    array_splice($pages, $num, 0, $alias);
                } else { 
                    $position = 'bottom';
                }
            }

            // Top / bottom position
            if ($position == 'top') { 
                array_unshift($pages, $alias);
            } else { 
                array_push($pages, $alias);
            }
        }

        // Get new pages
        $new_pages = [];
        foreach ($pages as $alias) { 
            $new_pages[$alias] = $tab_pages[$alias];
        }

        // Return
        return $new_pages;
    }

}


