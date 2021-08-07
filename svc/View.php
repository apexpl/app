<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\Svc\{App, Container};
use Apex\Syrus\Render\Templates;
use Apex\Syrus\Parser\{Parser, VarContainer, Common};
use Psr\Http\Message\UriInterface;
use Apex\Syrus\Exceptions\SyrusTemplateNotFoundException;

/**
 * Syrus  view
 */
final class View extends \Apex\Syrus\Syrus
{

    /**
     * Constructor
     */
    public function __construct(
        private App $app, 
        private Container $cntr
    ) {
        $this->template_dir = SITE_PATH . '/views';
    }

    /**
     * Render
     */
    public function render(string $file = ''):string
    {

        // Check auto-routing
        if ($file == '' && $this->template_file == '') { 
            $file = $this->doAutoRouting();
        }
        if ($file != '') { 
            $this->setTemplateFile($file);
        }

        // Load base vars
        $this->loadBaseVariables();

        // Render the template
        $tparser = $this->cntr->make(Templates::class, ['syrus' => $this]);
        $html = $tparser->render();

        // Render again, if no-cache items
        if (preg_match("/<s:(.+?)>/", $html)) { 
            $html = $this->renderBlock($html);
        }

        // Return
        return $html;
    }

    /**
     * Load base variables
     */
    private function loadBasevariables():void
    {

        // Set vars
        $vars = [
            'theme_uri' => '/themes/' . $this->getTheme(), 
            'is_auth' => $this->app->isAuth() === true ? 1 : 0, 
            'current_year' => date('Y') 
        ];

        // Add vars
        $this->assign('', $vars);
        $this->assign('config', $this->app->getAllConfig());
    }

}

