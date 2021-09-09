<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Render;

use Apex\Svc\View;
use Apex\App\Base\Web\Components;
use Apex\Syrus\Parser\StackElement;

/**
 * Render auto complete box
 */
class AutoComplete
{

    #[Inject(Components::class)]
    private Components $components;

    #[Inject(View::class)]
    private View $view;

    /**
     * Render
     */
    public function render(StackElement $e):string
    {

        // Perform checks
        if (!$auto_complete = $e->getAttr('autocomplete')) {
            return "<b>ERROR:</b> No 'autocomplete' attribute was found within the HTML tag.";
        } elseif (!$name = $e->getAttr('name')) { 
            return "<b>ERROR:</b> No 'name' attribute exists within the HTML tag to display auto-complete box.";
        }

        // Load component
        if (!$obj = $this->components->load('AutoComplete', $auto_complete, ['attr' => $e->getAttrAll()])) { 
            return "<b>ERROR:</b> Unable to load auto-complete class with alias '$auto_complete'";
        }

        // Set variables
        $idfield = $e->getAttr('idfield') ?? $name . '_id';
        $width = $e->getAttr('width') ?? '';
        $placeholder = $e->getAttr('placeholder') ?? '';

        // Set HTML
        $html = "<div class=\"form-group\">\n";
        $html .= "<input type=\"hidden\" name=\"$idfield\" value=\"\" id=\"$idfield\" />\n";
        $html .= "<input type=\"text\" name=\"$name\" id=\"$name\" ";
        if ($placeholder != '') { $html .= "placeholder=\"$placeholder\" "; }
        if ($width != '') { $html .= "style=\"width: $width;\" "; }
        $html .= "/>\n</div>\n";

        // Get Javascript
        $js = "\t\t\$( \"#" . $name . "\" ).autocomplete({ \n";
        $js .= "\t\t\tminlength: 2, \n";
        $js .= "\t\t\tsource: \"/ajax/webapp/search_auto_complete?autocomplete=$auto_complete\", \n";
        $js .= "\t\t\tselect: function(event, ui) { \$(\"#" . $idfield . "\").val(ui.item.data); }\n";
        $js .= "\t\t});\n";

        // Add Javascript to template
        $this->view->addJavascript($js);

    // Return
    return $html;
    }

}


