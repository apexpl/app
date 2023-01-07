<?php
declare(strict_types = 1);

namespace Apex\App\Adapters;

use Apex\Svc\{App, View, Container};
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Interfaces\LoaderInterface;
use Psr\Http\Message\UriInterface;
use Apex\App\Attr\Inject;

/**
 * Example loader class.  Copy this class, and modify as necessary for your 
 * own specific implementation.
 */
class SyrusAdapter implements LoaderInterface
{

    // Properties
    private array $yaml;

    /**
     * Constructor
     */
    public function __construct(
        private App $app,
        private View $view, 
        private Container $cntr
    ) {
        $this->yaml = $this->app->getRoutesConfig('site.yml');
    }

    /**
     * Get breadcrumbs
     *
     * Returns associative array, keys being the name displayed within the web browser, and values being the href to link to.  
     * If value is blank, element will not contain a hyperlink.
     */
    public function getBreadCrumbs(StackElement $e, UriInterface $uri):array
    {

        // Set array, two links, one text-only element
        $crumbs = [
            'Home' => '/index', 
            'Template Tags' => '/tags', 
            'Breadcrumbs' => ''
        ];

        // Return
        return $crumbs;
    }

    /**
     * Get social media links.
     *
     * Returns associative array, keys being the icon aliase (ie. FontAwesome) to display, and values being the URL to link to.
     */
    public function getSocialLinks(StackElement $e, UriInterface $uri):array
    {

        // Set social media links
        $links = [
            'twitter' => 'https://twitter.com/mdizak1', 
            'facebook' => 'https://facebook.com', 
            'youtube' => 'https://youtube.com'
        ];

        // Return
        return $links;
    }

    /**
     * Get value of placeholder
     *
     * Returns contents to replace <s:placeholder> tag with.
     */
    public function getPlaceholder(StackElement $e, UriInterface $uri):string
    {

        // Get contents
        $contents = '<example Placeholder';

        // Return
        return $contents;
    }

    /**
     * Get theme
     */
    public function getTheme():string
    {

        // Check themes are defined
        if (!isset($this->yaml['themes'])) { 
            return 'default';
        }

        // Get uri
        $file = $this->view->getTemplateFile();

        // Go through themes
        $theme = $this->yaml['themes']['default'] ?? 'default';
        foreach ($this->yaml['themes'] as $chk => $alias) { 

        if (!str_starts_with($file, $chk)) { 
            continue;
            }
            $theme = $alias;
            break;
        }
        $this->view->setTheme($theme);
        $this->cntr->set('theme', $theme);

        // Return
        return $theme;
    }

    /**
     * Get page var
     */
    public function getPageVar(string $var_name):?string
    {

        // Check page var is defined
        if (!isset($this->yaml['page_vars'])) { 
            return '';
        } elseif (!isset($this->yaml['page_vars'][$var_name])) { 
            return '';
        }
        $yaml_vars = $this->yaml['page_vars'][$var_name];

        // Get template file
        $file = $this->view->getTemplateFile();

        // Go through page vars
        $value = $yaml_vars['default'] ?? '';
        if (isset($yaml_vars[$file])) { 
            $value = $yaml_vars[$file];
        }

        // Return
        return $value;
    }

    /**
     * Check nocache pages
     */
    public function checkNoCachePage(string $file):bool
    {

        // Check nocache_tags exist
        if (!isset($this->yaml['nocache_pages'])) { 
            return false;
        }

        // Return
        return in_array($file, $this->yaml['nocache_pages']);
    }

    /**
     * Check nocache tags
     */
    public function checkNoCacheTag(string $tag_name):bool
    {

        // Check nocache_tags exist
        if (!isset($this->yaml['nocache_tags'])) { 
            return false;
        }

        // Return
        return in_array($tag_name, $this->yaml['nocache_tags']);
    }

}


