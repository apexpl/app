<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Tests;

use Apex\Svc\{Di, Db, View};
use PHPUnit\Framework\TestCase;

/**
 * Custom assertions
 */
class CustomAssertions extends TestCase
{

    // Properties
    protected string $res_body = '';

    /**
     * assertFileContains
     */
    final public function assertFileContains(string $text, string $filename):void
    {
        $this->checkFileContains($text, $filename, true);
    }

    final public function assertFileNotContains(string $text, string $filename):void
    {
        $this->checkFileContains($text, $filename, false);
    }

    private function checkFileContains(string $text, string $filename, bool $has = true):void
    { 

        // Check file exists
        if (!file_exists($filename)) { 
            $this->assertFileExists($filename);
            return;
        }
        $contents = file_get_contents($filename);

        // Check contains
        $method = $has === true ? 'assertStringContainsString' : 'assertStringNotContainsString';
        $this->$method($text, $contents);
    }

    /**
     * assertHasBvRow
     */
    final public function assertHasDbRow(string $sql, ... $args):void
    {
        $this->checkHasDbRow($sql, true, ...$args);
    }

    final public function assertNotHasDbRow(string $sql, ...$args):void
    {
        $this->checkHasDbRow($sql, false, ...$args);
    }

    private function checkHasDbRow(string $sql, bool $has = true, ...$args):void
    { 

        // Check db row
        $db = Di::get(Db::class);
        $row = $db->getRow($sql, ...$args);

        // Assert
        $method = $has === true ? 'assertNotNull' : 'assertNull';
        $this->$method($row, "Database row exists with SQL, $sql");
    }

    /** 
     * Check single database field
     */
    final public function assertHasDBField(string $sql, string $column, string $value) { $this->checkHasDBField($sql, $column, $value, true); }
    final public function assertNotHasDBField(string $sql, string $column, string $value) { $this->checkHasDBField($sql, $column, $value, false); }
    private function checkHasDBField(string $sql, string $column, string $value, bool $has = true)
    { 

        // Perform check
        $ok = false;
        $db = Di::get(Db::class);
        if ($row = $db->getRow($sql)) { 
            $ok = isset($row[$column]) && $row[$column] == $value ? true : false;
        }

        // Assert
        if ($ok !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("Database row does $not contain a column with the name {1} with the value {2}, retrived from the SQL query: {3}", $column, $value, $sql));
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Assert page title matches
     */
    final public function assertPageTitle(string $title)
    { 
        $this->checkPageTitle($title, true); 
    }

    final public function assertNotPageTitle(string $title)
    {
        $this->checkPageTitle($title, false);
    }

    private function checkPageTitle(string $title, bool $has = true)
    { 

        // Assert
        $view = Di::get(View::class);
        $chk_title = $view->getPageTitle();
        if ($has === true) { 
            $this->assertEquals($title, $chk_title, tr("Title of page at {1}/{2} does NOT equal the title: {3}", app::get_area(), app::get_uri(), $title));
        } else { 
            $this->assertNotEquals($title, $chk_title, tr("Title of page at {1}/{2} does equal the title: {3}", app::get_area(), app::get_uri(), $title));
        }
    }

    /**
     * Page title contains
     */
    final public function assertPageTitleContains(string $text) { $this->checkPageTitleContains($text, true); }
    final public function assertPageTitleNotContains(string $text) { $this->checkPageTitleContains($text, false); }
    private function checkPageTitleContains(string $text, bool $has = true)
    { 

        // Assert
        $view = Di::get(View::class);
        $ok = strpos($view->getPageTitle(), $text) === false ? false : true;
        if ($ok !== $has) { 
            $not = $has === true ? ' NOT ' : array();
            $this->asserttrue(false, tr("Title of page {1}/{2} does $not contain the text: {3}", app::get_area(), app::get_uri(), $text));
        } else { 
            $this->asserttrue(true);
        }
    }

    /**
     * Check if http response body contains text
     */
    final public function assertPageContains(string $text) { $this->checkPageContains($text, true); }
    final public function assertPageNotContains(string $text) { $this->checkPageContains($text, false); }
    private function checkPageContains(string $text, bool $has = true)
    { 

        // Check
        $ok = strpos($this->res_body, $text) === false ? false : true;

        // Assert
        if ($ok !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The page {1}/{2} does $not contain the text {3}", app::get_area(), app::get_uri(), $text));
        } else { 
            $this->asserttrue(true);
        }
    }

    /**
     * Check if page has call out
     */
    final public function assertHasCallout($type = 'success', $text = '') { $this->checkHasCallout($type, $text, true); }
    final public function assertNotHasCallout($type = 'success', $text = '') { $this->checkHasCallout($type, $text, true); }
    private function checkHasCallout(string $type, string $text = '', bool $has = true)
    { 

        // Get the messages to check
        $view = Di::get(View::class);
        $msg = $view->getCallouts();
        if (!isset($msg[$type])) { $msg[$type] = array(); }

        // Check message type
        $found = count($msg[$type]) > 0 && $text == '' ? true : false;

        // Check for text, if needed
        if ($text != '') { 
            foreach ($msg[$type] as $message) { 
                if (strpos($message, $text) !== false) { $found = true; }
            }

            // Ensure it appears on page
            if (strpos($this->res_body, $text) === false) { $found = false; }
        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The page {1}/{2} does $not contain a user message of type {3} that contains the text: {4}", app::get_area(), app::get_uri(), $type, $text));
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Check form validation error
     */
    final public function assertHasFormError(string $type, string $name) { $this->checkHasFormError($type, $name, true); }
    final public function assertNotHasFormError(string $type, string $name) { $this->checkHasFormError($type, $name, false); }
    private function checkHasFormError(string $type, string $name, bool $has = true)
    { 

        // Set variables
        $name = ucwords(str_replace("_", " ", $name));
        $view = Di::get(View::class);
        $errors = $view::getCallouts()['error'] ?? [];

        // Create message
        if ($type == 'blank') { $msg = "The form field $name was left blank, and is required"; }
        elseif ($type == 'email') { $msg = "The form field $name must be a valid e-mail address."; }
            elseif ($type == 'alphanum') { $msg = "The form field $name must be alpha-numeric, and can not contain spaces or special characters."; }
            elseif ($type == 'decimal') { $msg = "The form field $name can only be a decimal / amount."; }
        elseif ($type == 'alphanum') { $msg = "The form field $name must be alpha-numeric, and can not contain spaces or special characters."; }
        else { return; }

        // Check messages
        $found = false;
        foreach ($errors as $message) { 
            if (strpos($msg, $message) !== false) { $found = true; }
        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The page {1}/{2} does $not contain a form error of type: {3} for the form field: {4}", app::get_area(), app::get_uri(), $type, $name));
        } else { 
            $this->asserttrue(true);
        }
    }

    /**
     * Check for heading
     */
    final public function assertHasHeading($hnum, string $text) { $this->checkHasHeading($hnum, $text, true); }
    final public function assertNotHasHeading($hnum, string $text) { $this->checkHasHeading($hnum, $text, false); }
    private function checkHasHeading($hnum, string $text, bool $has = true)
    { 

        // Check for heading
        $found = false;
        preg_match_all("/<h" . $hnum . ">(.*?)<\/h" . $hnum . ">/si", $this->res_body, $hmatch, PREG_SET_ORDER);
        foreach ($hmatch as $match) { 
            if ($match[1] == $text) { $found = true; }
        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The page {1}/{2} does $not contain a heading of h{3} with the text: {4}", app::get_area(), app::get_uri(), $hnum, $text));
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Check for submit button
    final public function assertHasSubmit(string $value, string $label) { $this->checkHasSubmit($value, $label, true); }
    final public function assertNotHasSubmit(string $value, string $label) { $this->checkHasSubmit($value, $label, false); }
    private function checkHasSubmit(string $value, string $label, $has = true)
    { 

        // Set variables
    $html = $this->res_body;
        $chk = "<button type=\"submit\" name=\"submit\" value=\"$value\" class=\"btn btn-primary btn-lg\">$label</button>";

        // Assert
        $ok = strpos($html, $chk) === false ? false : true;
        if ($ok !== $has) { 
            $word = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, "The page does $word contain a submit button with the value: $value, and label: $label");
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Check has data table
     */
    final public function assertHasTable(string $table_alias) { $this->checkHasTable($table_alias, true); }
    final public function assertNotHasTable(string $table_alias) { $this->checkHasTable($table_alias, false); }
    private function checkHasTable(string $table_alias, bool $has = true)
    { 

        // Set variables
        $html = $this->res_body;
        $chk = 'tbl_' . str_replace(":", "_", $table_alias);

        // GO through all tables on page
        $found = false;
        preg_match_all("/<table(.*?)>/si", $html, $table_match, PREG_SET_ORDER);
        foreach ($table_match as $match) { 
            $attr = view::parse_attr($match[1]);
            $id = $attr['id'] ?? '';
            if ($id == $chk) { $found = true; }
        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The page {1}/{2} does $not contain a table with the alias: {3}", app::get_area(), app::get_uri(), $table_alias));
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Check for specfic table row and column value
     */
    final public function assertHasTableField(string $table_alias, int $col_num, string $value) { $this->checkHasTableField($table_alias, $col_num, $value, true); }
    final public function assertNotHasTableField(string $table_alias, int $col_num, string $value) { $this->checkHasTableField($table_alias, $col_num, $value, false); }
    private function checkHasTableField(string $table_alias, int $column_num, string $value, bool $has = true)
    { 

        // Set variables
        $html = $this->res_body;
        $table_alias = 'tbl_' . str_replace(":", "_", $table_alias);

        // Go through tables
        $found = false;
        preg_match_all("/<table(.+?)>(.*?)<\/table>/si", $html, $table_match, PREG_SET_ORDER);
        foreach ($table_match as $match) { 

            // Check table ID
            $attr = view::parse_attr($match[1]);
            $id = $attr['id'] ?? '';
            if ($id != $table_alias) { continue; }

            // Get tbody contents
            if (!preg_match("/<tbody(.*?)>(.*?)<\/tbody>/si", $match[2], $tbody)) { 
                continue;
            }

            // Go through all rows
            preg_match_all("/<tr>(.*?)<\/tr>/si", $tbody[2], $row_match, PREG_SET_ORDER);
            foreach ($row_match as $row) { 

                // Go through cells
                preg_match_all("/<td(.*?)>(.*?)<\/td>/si", $row[1], $cell_match, PREG_SET_ORDER);
                $chk = $cell_match[$column_num][2] ?? '';

                if ($chk == $value) { 
                    $found = true;
                    break;
                }

            }
            if ($found === true) { break; }

        }

        // Assert
        if ($found !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("On the page {1}/{2} the table with alias {3} does $not have a row that contains the text {4} on column number {5}", app::get_area(), app::get_uri(), $table_alias, $value, $column_num));
        } else { 
            $this->asserttrue(true);
        }

    }

    /**
     * Check if form field exists
     */
    final public function assertHasFormField($name) { $this->checkHasFormField($name, true); }
    final public function assertNotHasFormField($name) { $this->checkHasFormField($name, false); }
    private function checkHasFormField($name, bool $has = true)
    { 

        // Get names
        $fields = is_array($name) ? $name : array($name);

        // Get HTML
        $html = $this->res_body;

        // Go through fields
        foreach ($fields as $name) { 

            // Go through form fields
            $found = false;
            preg_match_all("/<input(.*?)>/si", $html, $field_match, PREG_SET_ORDER);
            foreach ($field_match as $match) { 
                $attr = view::parse_attr($match[1]);
                if (isset($attr['name']) && $attr['name'] == $name) { 
                    $found = true;
                    break;
                }
            }

            // Go through select lists
            preg_match_all("/<select(.*?)>/si", $html, $select_match, PREG_SET_ORDER);
            foreach ($select_match as $match) { 
                $attr = view::parse_attr($match[1]);
                if (isset($attr['name']) && $attr['name'] == $name) { 
                    $found = true;
                    break;
                }
            }

            // Assert
            if ($found !== $has) { 
                $not = $has === true ? ' NOT ' : '';
                $this->asserttrue(false, tr("Page at {1}/{2} does $not contain a form field with the name {3}", app::get_area(), app::get_uri(), $name));
            } else { 
                $this->asserttrue(true);
            }

        }

    }

    /**
     * Check string contains text
     */
    final public function assertStringContains(string $string, string $text) { $this->checkStringContains($string, $text, true); }
    final public function assertStringNotContains(string $string, string $text) { $this->checkStringContains($string, $text, false); }
    private function checkStringContains(string $string, string $text, bool $has = true)
    { 

        // Check
        $ok = strpos($string, $text) === false ? false : true;
        if ($ok !== $has) { 
            $not = $has === true ? ' NOT ' : '';
            $this->asserttrue(false, tr("The provided string does $not contain the text: {1}", $text));
        } else { 
            $this->asserttrue(true);
        }

    }

}


