<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Render;

use Apex\Svc\{Container, View, Convert};
use Apex\App\Base\Web\Components;
use Apex\Syrus\Parser\StackElement;
use Apex\App\Base\Web\Utils\DataTableDetails as TableDetails;
use Apex\App\Interfaces\Opus\DataTableInterface;

/**
 * Render data table
 */
class DataTable
{

    #[Inject(Container::class)]
    private Container $cntr;

    #[Inject(Convert::class)]
    private Convert $convert;

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
        if (!$table = $e->getAttr('table')) { 
            return "<b>ERROR:</b> No 'table' attribute exists within the function tag.";
        } elseif (!$obj = $this->components->load('DataTable', $table, ['attr' => $e->getAttrAll()])) { 
            return "<b>ERROR:</b> No data table component exists with the alias '$table'";
        }

        // Set variables
        $attr = $e->getAttrAll();
        if (!isset($attr['id'])) { 
            $attr['id'] = $this->convert->case('tbl' . str_replace('.', '_', $table), 'camel');
        }
        $sortable = $obj->sortable ?? [];

        // Get table defailts
        $details = $this->cntr->make(TableDetails::class, [
            'table' => $obj, 
            'divid' => $attr['id']
        ]);

        // Create header line
        $tpl = $this->createHeaderLine($attr, $obj, $details);

        // Add radio / checkbox header column
        $form_type = $obj->form_field ?? 'none';
        if (in_array($form_type, ['radio','checkbox'])) { 
            $tpl .= "    <th>&nbsp;</th>\n";
            $form_name = $form_type == 'checkbox' ? $obj->form_name . '[]' : $obj->form_name;
        }

        // Go through header columns
        foreach ($obj->columns as $alias => $name) { 
            $has_sort = in_array($alias, $sortable) ? 1 : 0;
            $tpl .= "    <th has_sort=\"$has_sort\" alias=\"$alias\">$name</th>\n";
        }
        $tpl .= "</tr></thead><tbody id=\"" . $attr['id'] . "_tbody\">\n";

        // Go through rows
        foreach ($details->rows as $row) { 
            $tpl .= "<tr>\n";

            // Add form field
            if (in_array($form_type, ['radio','checkbox'])) { 
                $tpl .= "    <td align=\"center\"><s:$form_type name=\"$form_name\" value=\"" . $row[$obj->form_value] . "\"></td>\n";
            }

            // Columns
            foreach ($obj->columns as $alias => $name) { 
                $value = $row[$alias] ?? "&nbsp;";
                $tpl .= "<    <td>$value</td>\n";
            }
            $tpl .= "</tr>\n";
        }

        // Add delete button, if needed
        $delete_button = $obj->delete_button ?? '';
        if ($delete_button != '') {
            $tpl .= "\n<s:button_group>\n";
            $tpl .= "    <s:button href=\"javascript:ajaxConfirm('Are you sure you want to delete the checked records?', 'webapp/delete_checked_rows', 'table=" . $table . "&id=" . $attr['id'] . "');\" label=\"Delete Checked Rows\">\n";
            $tpl .= "</s:button_group>\n\n";
        }

        // Return
        $tpl .= "</tbody></s:data_table>\n\n";
        return $this->view->renderBlock($tpl);
    }

    /**
     * Create header tpl line
     */
    private function createHeaderLine(array $attr, DataTableInterface $table, TableDetails $details):string
    {

        // Get AJAX data
        unset($attr['alias']);
        $ajax_data = http_build_query($attr);

        // Create table attributes
        $table_attr = [
            'id' => $attr['id'], 
            'has_search' => isset($table->has_search) && $table->has_search === true ? 1 : 0, 
            'total_rows' => $details->total_rows, 
            'rows_per_page' => $details->rows_per_page, 
            'current_page' => $details->page, 
            'search_href' => "javascript:ajaxSend('webapp/search_table', '$ajax_data', 'search_" . $attr['id'] . "');", 
            'sort_href' => "javascript:ajaxSend('webapp/sort_table', '$ajax_data&sort_col=~col_alias~&sort_dir=~sort_dir~');", 
            'pgn_href' => "javascript:ajaxSend('Webapp/navigateTable', '$ajax_data&page=~page~');" 
        ];

        // Get TPL code
        $tpl = "<s:data_table ";
        foreach ($table_attr as $key => $value) { 
            $tpl .= $key . '="' . $value . '" ';
        }
        $tpl .= ">\n<thead><tr>\n";

        // Return
        return $tpl;
    }

}

