<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\App\Pkg\Gpt\GptController;

/**
 * GPT - Data Table
 */
class GptForm extends GPtClient
{

    #[Inject(GptController::class)]
    private GptController $gpt_controller;

    // Properties
    private array $new_files = [];

    /**
     * Initial transformation
     */
    public function initial(string $pkg_alias, string $form_class, string $controller_class, $dbtable, array $hashes):array
    {

        // Initialize
        $cols = $this->db->getColumnDetails($dbtable);
        $fkeys = $this->db->getForeignKeys($dbtable);
        $lines = file(SITE_PATH . $form_class);

        // Go through table columns
        foreach ($cols as $col_name => $vars) {
            $hash_alias = str_replace(str_replace('-', '_', $pkg_alias) . '_', '', $dbtable) . '_' . strtolower($col_name);

            // Check for uuid
            if ($col_name == 'uuid' && $line_num = $this->getLineNumber('uuid', $lines)) {
                $lines = $this->changeUuidToUsername($form_class, $lines, $line_num);
                if ($controller_class != '') {
                    $this->gpt_controller->changeUuidToUsername($controller_class);
                }
            } else if (in_array($hash_alias, $hashes) && $line_num = $this->getLineNumber($col_name, $lines)) {
                $lines[$line_num] = "            '$col_name' => \$builder->select()->required()->dataSource('hash." . $pkg_alias . '.' . $hash_alias . "'),\n";
            } elseif (isset($fkeys[$col_name]) && $line_num = $this->getLineNumber($col_name, $lines)) {
                $lines = $this->addForeignKey($pkg_alias, $form_class, $col_name, $fkeys[$col_name]['column'], $fkeys[$col_name]['table'], $line_num, $lines);
            }
        }

        // Save form class
        file_put_contents(SITE_PATH . $form_class, implode("", $lines));
        return $this->new_files;
    }

    /**
     * Modify form and change 'uuid' to 'username' when updating.
 */
    public function changeUuidToUsername(string $form_class, array $lines, int $line_num):array
    {

        // Change 'uuid' form field
        $lines[$line_num] = "            'username' => \$uuid_field,\n";
        $form_code = implode("", $lines);

        // Add use declaration
        $form_code = str_replace(
            "use Apex\\App\\Base\\Web\\Utils\\FormBuilder;\n",
            "use Apex\\App\\Base\\Web\\Utils\\FormBuilder;\nuse App\\Users\\User;\n",
            $form_code
        );

        // Add uuid_field generation
        $form_code = str_replace("        // Set form fields\n", base64_decode('ICAgICAgICAvLyBHZXQgdXNlcm5hbWUgZmllbGQKICAgICAgICBpZiAoaXNzZXQoJGF0dHJbJ3JlY29yZF9pZCddKSAmJiAkYXR0clsncmVjb3JkX2lkJ10gIT0gJycpIHsKICAgICAgICAgICAgJHV1aWRfZmllbGQgPSAkYnVpbGRlci0+dGV4dGJveCgpLT5yZXF1aXJlZCgpOwogICAgICAgIH0gZWxzZSB7CiAgICAgICAgICAgICR1dWlkX2ZpZWxkID0gJGJ1aWxkZXItPnR3b2NvbCgpLT5sYWJlbCgnVXNlcicpLT5jb250ZW50cygnPHM6ZnVuY3Rpb24gbmFtZT0idXNlciIgYWxpYXM9ImRpc3BsYXlfYXV0b19jb21wbGV0ZSIgYXV0b2NvbXBsZXRlPSJ1c2Vycy5maW5kIj4nKTsKICAgICAgICB9CgogICAgICAgIC8vIFNldCBmb3JtIGZpZWxkcwo='), $form_code);

        // Update getRecord() method
        $form_code = str_replace(
            "        // Return\n        return \$row;\n",
            base64_decode('ICAgICAgICAvLyBHZXQgdXNlcgogICAgICAgICR1c2VyID0gVXNlcjo6bG9hZFV1aWQoJHJvd1sndXVpZCddKTsKICAgICAgICAkcm93Wyd1c2VybmFtZSddID0gJHVzZXItPmdldFVzZXJuYW1lKCk7CgogICAgICAgIC8vIFJldHVybgogICAgICAgIHJldHVybiAkcm93OwoK'),
            $form_code
        );

        // Save and return
        file_put_contents(SITE_PATH . $form_class, $form_code);
        return file(SITE_PATH . $form_class);
    }

    /**
     * Add form key select list / auto-complete to form
     */
    private function addForeignKey(string $pkg_alias, string $form_class, string $col_name, string $ref_col_name, string $dbtable, int $line_num, array $lines):array
    {

        // GEt item description and sort by
        list($item_desc, $sort_by) = $this->getItemDescription($dbtable, true);
        $label = $this->convert->case(preg_replace("/_id$/", "", $col_name), 'phrase');

        // Check if auto-complete or select
        $chat = $this->initChat($pkg_alias);
        $chk = $this->send("Responding with only 'yes' or 'no', does the project description state whether or not the '$col_name' column of the '$dbtable' database table should be an auto-complete list?", $chat);

        // Change form field to auto-complete or select list
        if (strtolower($chk) == 'yes') {
            $this->new_files[] = $this->gpt_auto_complete->generate($pkg_alias, $dbtable, $item_desc);
            $alias = str_replace(str_replace('-', '_', $pkg_alias) . '_', '', $dbtable) . '_find';
            $label = $this->convert($alias, 'phrase');
            $lines[$line_num] = "            '$col_name' => \$builder->twocol()->label('$label')->contents('<s:function name=\"$col_name\" alias=\"display_auto_complete\" autocomplete=\"$alias\">'),\n";
        } else {
            $data_source = implode('.', ['table', $dbtable, $item_desc, $sort_by]);
            $lines[$line_num] = "        '$col_name' => \$builder->select()->required()->dataSource('$data_source')->label('$label'),\n";
        }

        // Update form code
        return $lines;
    }

    /**
     * Get form class line
     */
    private function getLineNumber(string $col_name, array $lines):?int
    {

        // Initialize
        $line_num = null;
        $search = "            '$col_name' => ";

        // Fo through lines
        $x = -1;
        foreach ($lines as $line) {
            $x++;

            if (str_starts_with($line, $search)) {
                $line_num = $x;
                break;
            }
        }

        // Return
        return $line_num;
    }

}


