<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\Svc\{App, Container, Db};
use Apex\Syrus\Render\Templates;
use Apex\Syrus\Parser\{Parser, VarContainer, Common};
use Apex\Syrus\Render\Tags;
use Psr\Http\Message\UriInterface;
use Apex\Syrus\Exceptions\SyrusTemplateNotFoundException;

/**
 * Syrus  view
 */
final class View extends \Apex\Syrus\Syrus
{

    #[Inject(App::class)]
    private App $app;

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Db::class)]
    private Db  $db;

    // Properties
    private string $javascript = '';

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

        // Apply Javascript
        $html = $this->applyJavascript($html);

        // Return
        return $html;
    }

    /**
     * Add Javascript
     */
    public function addJavascript(string $js):void
    {
        $this->javascript .= $js;
    }

    /**
     * Load base variables
     */
    private function loadBasevariables():void
    {

        // Get user type
        $user_type = $this->app->getUser()?->getType();
        if ($user_type === null) { 
            $user_type = 'public';
        }

        // Set vars
        $vars = [
            'theme_uri' => '/themes/' . $this->getTheme(), 
            'is_auth' => $this->app->isAuth() === true ? 1 : 0, 
            'user_type' => $user_type,
            'current_year' => date('Y'),
            'path' => $this->app->getPath()
        ];

        // Set site variables
        $site = [
            'name' => $this->app->config('core.site_name'),
            'address' => $this->app->config('core.site_address'),
            'address2' => $this->app->config('core.site_address2'),
            'email' => $this->app->config('core.site_email'),
            'phone' => $this->app->config('core.site_phone'),
            'tagline' => $this->app->config('core.site_tagline'),
            'about' => $this->app->config('core.site_about'),
            'facebook' => $this->app->config('core.site_facebook'),
            'twitter' => $this->app->config('core.site_twitter'),
            'instagram' => $this->app->config('core.site_instagram'),
            'linkedin' => $this->app->config('core.site_linkedin'),
            'youtube' => $this->app->config('core.site_youtube'),
            'reddit' => $this->app->config('core.site_reddit'),
            'github' => $this->app->config('core.site_github')
        ];

        // Assign vars
        $this->assign('', $vars);
        $this->assign('site', $site);
        $this->assign('config', $this->app->getAllConfig());

        // Get message counters
        $this->getMessageCounters();
    }

    /**
     * Get message counters
     */
    private function getMessageCounters():void
    {

        // Set initial vars
        $counters = [
            'total_alerts' => 0,
            'unread_alerts' => 0,
            'total_messages' => 0,
            'unread_messages' => 0
        ];

        // Check if authenticated
        if (!$this->app->isAuth()) {
            $this->assign('', $counters);
            return;
        }

        // Go through totals
        $rows = $this->db->query("SELECT is_read,type,count(*) total FROM alerts WHERE uuid = %s GROUP BY type,is_read", $this->app->getUuid());
        foreach ($rows as $row) {
            $type = $row['type'] . 's';
            $counters['total_' . $type] += $row['total'];
            if ((bool) $row['is_read'] === false) {
                $counters['unread_' . $type] += $row['total'];
            }
        }

        // Assign
        $this->assign('', $counters);
    }

    /**
     * Apply Javascript
     */
    private function applyJavascript(string $html):string
    {

        // Add system CSS
        $html = str_replace("<body>", base64_decode('Cgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoKICAgIC5mb3JtX3RhYmxlIHsgbWFyZ2luLWxlZnQ6IDI1cHg7IH0KICAgIC5mb3JtX3RhYmxlIHRkIHsKICAgICAgICB0ZXh0LWFsaWduOiBsZWZ0OwogICAgICAgIHZlcnRpY2FsLWFsaWduOiB0b3A7CiAgICAgICAgcGFkZGluZzogOHB4OwogICAgfQoKPC9zdHlsZT4KCgoKCgo=') . '<body>', $html);

        // Check if Javascript enabled
        if ($this->app->config('core.enable_javascript') != 1 && $this->app->getArea() != 'admin') { 
            return $html;
        }

        // Add any defined Javascript
        if ($this->javascript != '') { 
            $html = str_replace("</body>", "\t<script type=\"text/javascript\">\n" . $this->javascript . "\n\t</script>\n\n</body>", $html);
        }

        // Set Apex Javascript
        $js = "\t" . '<script type="text/javascript" src="/plugins/apex.js"></script>' . "\n";
        $js .= "\t" . '<script src="/plugins/parsley.js/parsley.min.js" type="text/javascript"></script>' . "\n";
        $js .= "\t" . '<script src="https://www.google.com/recaptcha/api.js"></script>' . "\n\n";
        $js .= "</head>\n\n";

        // Add to HTML
        $html = str_replace("</head>", $js, $html);

        // Add modal HTML
        $tags = $this->cntr->make(Tags::class);
        $modal = $tags->getSnippet('modal', '', []);
        $html = str_replace("<body>", "<body>\n\n$modal\n", $html);

        // Return
        return $html;
    }

}

