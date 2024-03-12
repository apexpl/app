<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web;

use Apex\App\Base\Web\Components;
use Apex\App\Base\Web\Utils\DataTableDetails;
use Apex\App\Exceptions\ApexComponentNotExistsException;
use Apex\App\Attr\Inject;

/**
 * AJAX library
 */
abstract class Ajax
{

    #[Inject(Components::class)]
    protected Components $components;

    // Properties
    public array $results = [];

    /**
     * Add action
     */
    final protected function add(string $action, array $vars)
    { 
        $vars['action'] = $action;
        $this->results[] = $vars;
    }

    /**
     * Alert
     */
    final public function alert(string $message):void
    { 
        $this->add('alert', array('message' => $message));
    }

    /**
     * Redirect
     */
    final public function redirect(string $url):void
    {
        $this->add('redirect', array('url' => $url));
    }

    /**
     * Clear table rows
     */
    final public function clearTable(string $divid):void
    {
        $this->add('clear_table', array('divid' => $divid));
    }

    /**
     * Remove checked table rows
     */
        final public function removeCheckedRows(string $divid):void
    { 
        $this->add('remove_checked_rows', array('divid' => $divid));
    }

    /**
     8 Add data rows
     */
    final public function addDataRows(string $divid, string $table_alias, array $rows, array $data = []):void
    { 

        // Check table component
        if (!$table = $this->components->load('DataTable', $table_alias, ['attr' => $data])) { 
            throw new ApexComponentNotExistsException("Data table does not exist with the alias, $table_alias");
        }

        // Check form field
        $form_field = $table->form_field ?? 'none';
        if ($form_field == 'checkbox' && !preg_match("/\[\]$/", $table->form_name)) { 
            $table->form_name .= '[]';
        }

        // Go through rows
        foreach ($rows as $row) { 

            // Add radio / checkbox, if needed
            $frow = [];
            if ($form_field == 'radio' || $form_field == 'checkbox') { 
                $frow[] = "<center><input type=\"$form_field\" name=\"" . $table->form_name . "\" value=\"" . $row[$table->form_value] . "\"></center>";
            }

            // Go through table columns
            foreach ($table->columns as $alias => $name) { 
                $value = $row[$alias] ?? '';
                $frow[] = $value;
            }

            // AJAX
            $this->add('add_data_row', array('divid' => $divid, 'cells' => $frow));
        }
    }

    /**
     * Set pagination
     */
    final public function setPagination(string $divid, DataTableDetails $details):void
    { 

        // Get nav function
        $vars = $this->app->getAllPost();
        unset($vars['page']);
        $nav_func = "<a href=\"javascript:ajaxSend('webapp/navigate_table', '" . http_build_query($vars) . "&page=~page~', 'none');\">";

        // Set variables
        list($max_items, $half_items) = [10, 5];
        $total_pages = ceil($details->total_rows / $details->rows_per_page);
        $pages_remaining = ceil(($details->total_rows - ($details->page * $details->rows_per_page)) / $details->rows_per_page);
        $start_page = ($pages_remaining > $half_items && $details->page > $half_items) ? ($details->page - $half_items) : 1;

        // Get end page
        $max_items = ($start_page > $half_items) ? $half_items : ($max_items - $details->page);
        $end_page = ($pages_remaining > $max_items) ? ($details->page + $max_items) : $total_pages;

        // Set AJAX
        $this->add('set_pagination', array(
            'divid' => preg_replace('/^tbl/', '', $divid),
            'start' => $details->start,
            'total' => $details->total_rows,
            'page' => $details->page,
            'start_page' => $start_page,
            'end_page' => $end_page,
            'rows_per_page' => $details->rows_per_page,
            'total_pages' => $total_pages,
            'nav_func' => $nav_func)
        );

    }

    /**
     * Prepend string
     */
    final public function prepend(string $divid, string $html):void
    { 
        $this->add('prepend', array('divid' => $divid, 'html' => $html));
    }

    /**
     * Append string
     */
    final public function append(string $divid, string $html):void
    { 
        $this->add('append', array('divid' => $divid, 'html' => $html));
    }

    /**
     * Play sound
     */
    final public function playSound(string $wav_file):void
    { 
        $this->add('play_sound', array('sound_file' => $wav_file));
    }

    /**
     * Set text
     */
    final public function setText(string $divid, $text):void
    { 
        $this->add('set_text', array('divid' => $divid, 'text' => $text));
    }

    /**
     * Set display
     */ 
    final public function setDisplay(string $divid, string $display):void
    { 
        $this->add('set_display', array('divid' => $divid, 'display' => $display));
    }

    /**
     * Clear list
     */
    final public function clearList(string $divid):void
    { 
        $this->add('clear_list', array('divid' => $divid));
    }

}


