<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web;

use Apex\App\Base\Web\Components;
use Apex\App\Exceptions\ApexComponentNotExistsException;

/**
 * AJAX library
 */
class Ajax
{

    #[Inject(Components::class)]
    private Components $components;

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
        if (!$table = $this->components->load('DataTable', $table_alias, $data)) { 
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
    final public function setPagination(string $divid, array $details):void
    { 

        // Get nav function
        $vars = $this->app->getAllPost();
        unset($vars['page']);
        $nav_func = "<a href=\"javascript:ajax_send('core/navigate_table', '" . http_build_query($vars) . "&page=~page~', 'none');\">";

        // Set AJAX
        $this->add('set_pagination', array(
            'divid' => $divid,
            'start' => $details['start'],
            'total' => $details['total'],
            'page' => $details['page'],
            'start_page' => $details['start_page'],
            'end_page' => $details['end_page'],
            'rows_per_page' => $details['rows_per_page'],
            'total_pages' => $details['total_pages'],
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


