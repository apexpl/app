<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\Svc\{App, Db};
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
            $file = $this->doAutoRouting($this->app->getPath());
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
        if ($this->app->config('core.enable_javascript') == 1) {
            $html = $this->applyJavascript($html);
        } else {
            $html = $this->disableJavascript($html);
        }

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
     * Disable javascript
     */
    public function disableJavascript(string $html):string
    {

        // Go through search table buttons
        preg_match_all("/<a href=\"javascript:ajaxSend\(\'webapp\/search_table\',(.*?)\)(.*?)<\/a>/si", $html, $search_match, PREG_SET_ORDER);
        foreach ($search_match as $match) {

            // Set button html
            $button = "<form action=\"" . $this->app->getPath() . "\" method=\"POST\">\n";
            $button .= "<button type=\"submit\" class=\"btn btn-primary btn-md\" title=\"Search\">Search <a class=\"fa fa-fw fa-search\"></i></button>\n";
            $button .= "</form>\n";

            // Replace
            $html = str_replace($match[0], $button, $html);
        }

        // Go through delete rows buttons
        preg_match_all("/<a href=\"javascript:ajaxConfirm\((.*?)webapp\/delete_checked_rows(.*?)table=(.*?)\&(.*?)\)/si", $html, $delete_match, PREG_SET_ORDER);
        foreach ($delete_match as $match) {

            // Set button html
            $button = "<input type=\"hidden\" name=\"table\" value=\"$match[3]\">\n";
            $button .= "<button type=\"submit\" name=\"submit\" value=\"delete_table_rows\" class=\"btn btn-primary btn-lg\">Delete Checked Rows</button>\n";

            // Replace
            $html = str_replace($match[0], $button, $html);
        }

        // Modals
        preg_match_all("/<a href=\"javascript:openModal\((.*?)\)(.*?)>(.*?)<\/a>/si", $html, $modal_match, PREG_SET_ORDER);
        foreach ($modal_match as $match) {

            // Get vars
            $vars = $this->parseJavascriptArgs($match[1]);

            // Replace
            $href = "<a href=\"/modal/" . $vars[0] . "?" . $vars[1] . "\">$match[3]</a>";
            $html = str_replace($match[0], $href, $html);
        }

        // Replace ajax calls
        preg_match_all("/<a href=\"javascript:(ajaxSend|ajaxConfirm)\((.*?)\)(.*?)>(.*?)<\/a>/si", $html, $ajax_match, PREG_SET_ORDER);
        foreach ($ajax_match as $match) {

            // Parse args, and start URI
            $vars = $this->parseJavascriptArgs($match[2]);
            if ($match[1] == 'ajaxConfirm') {
                array_splice($vars, 1, 1);
            }
            $query = 'ajax_function=' . urlencode($vars[0]) . '&' . $vars[1];

            // Replace
            $uri = $this->app->getPath() . '?' . $query;
            $href = "<a href=\"$uri\" $match[3]>$match[4]</a>";

            $html = str_replace($match[0], $href, $html);
        }

        // Return
        return $html;
    }

    /**
     * Parse Javascript args
     */
    private function parseJavascriptARgs(string $js_string):array
    {

        // GO through js string
        $vars = [];
        preg_match_all("/\'(.+?)\'/", $js_string, $js_match, PREG_SET_ORDER);
        foreach ($js_match as $match) {
            $vars[] = trim($match[1]);
        }

        // Return
        return $vars;
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
        if ($this->app->config('core.enable_javascript') != 1) {
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

