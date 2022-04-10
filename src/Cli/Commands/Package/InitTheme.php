<?php
declare(strict_types=1);

namespace Apex\App\Cli\Commands\Package;

use Apex\Svc\Convert;
use Apex\App\Cli\{Cli, CliHelpScreen};
use Apex\App\Sys\Utils\Io;
use Apex\App\Interfaces\Opus\CliCommandInterface;

/**
 * Init theme
 */
class InitTheme implements CliCommandInterface
{

    #[Inject(Convert::class)]
    private Convert $convert;

    #[Inject(Io::class)]
    private Io $io;

    /**
     * Process
     */
    public function process(Cli $cli, array $args):void
    {

        // Get theme alias
        $alias = $this->convert->case(($args[0] ?? ''), 'lower');
        $theme_dir = SITE_PATH . '/views/themes/' . $alias;
        if (!is_dir($theme_dir)) {
            $cli->error("Theme directory does not exist at /views/themes/$alias");
            return;
        }

        // GO through directories
        foreach (['includes', 'layouts', 'html'] as $dir) {

            // Shkip, if no directory
            if (!is_dir("$theme_dir/$dir")) {
                continue;
        }

        // Get through files
            $files = $this->io->parseDir("$theme_dir/$dir");
            foreach ($files as $file) {

                // Skip, if not .html
                if (!str_ends_with($file, '.html')) {
                    continue;
                }

                // Init file
                $this->initFile("$theme_dir/$dir/$file");
        }
        }

        // Success
        $cli->send("Successfully initialized the theme, $alias.\n\n");
    }

    /**
     * Init file
     */
    private function initFile(string $filename):void
    {

        // Get contents
        $html = file_get_contents($filename);

        // Add ~theme_uri~ to all tags
        preg_match_all("/<(script|link|img) (.*?)>/i", $html, $tag_match, PREG_SET_ORDER);
        foreach ($tag_match as $match) {

            // Check for source attribute
            $attr_name = $match[1] == 'link' ? 'href' : 'src';
            if (!preg_match("/$attr_name=\"(.+?)\"/i", $match[2], $attr_match)) { 
                continue;
            }

            // Skip, if already done
            if (preg_match("/^(http|~theme_uri~)/i", $attr_match[1])) {
                continue;
            }

            // Strip tag
            $uri = preg_replace("/^([\.\/]+)/", "", $attr_match[1]);

            // Update as necessary
            $attr = $attr_name . '="~theme_uri~/' . $uri . '"';
            $html = str_replace($match[0], str_replace($attr_match[0], $attr, $match[0]), $html);
        }

        // Page title
        $html = preg_replace("/<title>(.*?)<\/title>/si", "<title><s:page_title></title>", $html);
        $html = preg_replace("/{?\/}index\.html/", "/index", $html);
        if (str_ends_with($filename, 'header.html')) {
            $html .= "\n\n<s:callouts>\n\n";
        }

        // Save file
        file_put_contents($filename, $html);
    }

    /**
     * Help
     */
    public function help(Cli $cli):CliHelpScreen
    {

        $help = new CliHelpScreen(
            title: 'Initialize New Theme',
            usage: 'package init-theme <THEME<',
            description: 'Used immediately after splitting a new theme into header / footer includes.  This will go through the includes and automatically convert them for Apex, such as replacing links with ~theme_uri~ merge field, et al.'
        );

        $help->addParam('theme', 'The alias of the theme / package to initialize.');
        $help->addExample('./apex package init-theme my-new-theme');

        return $help;
    }

}

