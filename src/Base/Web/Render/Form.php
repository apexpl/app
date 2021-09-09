<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Render;

use Apex\Svc\View;
use Apex\App\Base\Web\Components;
use Apex\Syrus\Parser\StackElement;

/**
 * Render form
 */
class Form
{

    #[Inject(View::class)]
    private View $view;

    #[Inject(Components::class)]
    private Components $components;


    /**
     * Render
     */
    public function render(StackElement $e):string
    {

        // Check form alias
        if (!$form = $e->getAttr('form')) { 
            return "<b>ERROR:</b> No 'form' attribute exists within the function tag.";
        } elseif (!$obj = $this->components->load('form', $form)) { 
            return "<b>ERROR:</b> No form component exists with the alias '$form'";
        }

        // Get fields
        $fields = $obj->getFields($e->getAttrAll());

        // Get values, if record exists
        $values = [];
        if ($record_id = $e->getAttr('record_id')) {
            $values = $obj->getRecord($e->getAttr('record_id'));
        }
        $allow_post_values = $obj->allow_post_values ?? false;

        // Start HTML code
        $html = "<s:form_table>\n";
        foreach ($fields as $name => $vars) { 

            // Get value
            if (isset($values[$name])) { 
                $vars['value'] = $values[$name];
            } elseif ($allow_post_values === true && $this->app->hasPost($name)) { 
                $vars['value'] = $this->app->post($name);
            }

            // Create attr string
            $vars['name'] = $name;
            $attr_string = $this->createAttrString($vars);

            // Add to HTML
            $html .= "    <s:ft_" . $vars['field'] . " $attr_string>\n";
        }
        $html .= "</s:form_table>\n";

        // Return
        return $this->view->renderBlock($html);
    }

    /**
     * Create attr string
     */
    private function createAttrString(array $vars):string
    {

        // Go through
        $string = '';
        foreach ($vars as $key => $value) { 
            if ($key == 'field' || $value == '') { 
                continue;
            }
            $string .= $key . '="' . $value . '" ';
        }

        // Return
        return trim($string);
    }

}

