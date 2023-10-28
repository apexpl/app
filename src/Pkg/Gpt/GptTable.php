<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

/**
 * GPT - Data Table
 */
class GptTable extends GPtClient
{

    /**
     * Initial transformation
     */
    public function initial(string $pkg_alias, string $table_class, string $dbtable, array $hashes):void
    {

        // Get database columns
        $cols = $this->db->getColumnDetails($dbtable);
        $fkeys = $this->db->getForeignKeys($dbtable);
        $code = file_get_contents(SITE_PATH . $table_class);

        // Fix columns, if needed
        $code = $this->fixColumns($pkg_alias, $table_class, $dbtable, $code, $cols, $fkeys, $hashes);

        // Save code
        file_put_contents(SITE_PATH . $table_class, $code);
    }

    /**
     * Fix columns
     */
    private function fixColumns(string $pkg_alias, string $table_class, string $dbtable, string $code, array $cols, array $fkeys, array $hashes):string
    {

        // Initialize
        $columns = array_keys($cols);

        // Slim down number of columns, if too many
        if (count($columns) > 4) {
            $tmp_columns = $this->send("Using the below column names from the database table '$dbtable', select foure columns that are most suitable to be displayed within  a data table in a web browser to provide a summary of the rows to the user.  Only give four lines, one column name per-line and nothing else -- no summary, description or any other text.\n\n" . implode("\n", $columns) . "\n");
            $columns = explode("\n", $tmp_columns);
        }
        $columns[] = 'manage';

        // Generate column HTML
        $col_html = "    public array \$columns = [\n";
        foreach ($columns as $col_name) {
            $hash_alias = str_replace(str_replace('-', '_', $pkg_alias) . '_', '', $dbtable) . '_' . strtolower($col_name);

            if ($col_name == 'uuid') {
                $col_html .= "        'user' => 'User',\n";
                $code = $this->changeUuidToUsername($table_class, $code);
            } else {
                $col_html .= "        '$col_name' => '" . $this->convert->case(preg_replace("/_id$/", "", $col_name), 'phrase') . "',\n";
            }

            // Check for hash / foreign key
            if (in_array($hash_alias, $hashes)) {
                $code = $this->addHash($col_name, $pkg_alias . '.' . $hash_alias, $table_class, $code);
            } elseif (isset($fkeys[$col_name])) {
                $code = $this->addForeignKey($col_name, $fkeys[$col_name]['table'], $table_class, $code);
            }
        }

        // Replace columns property
        $col_html = preg_replace("/,\n$/", "\n    ];\n", $col_html);
        $code = preg_replace("/    public array \\\$columns (.*?)\]\;\n/si", $col_html, $code);

        // Return
        return $code;
    }

    /**
     * Change uuid to username
     */
    public function changeUuidToUsername(string $table_class, string $code):string
    {

        // Add use declaration
        $code = str_replace(
            "use Apex\\App\\Interfaces\\Opus\\DataTableInterface;\n",
            "use App\\Users\\User;\nuse App\\Users\\Exceptions\\UserNotExistsException;\nuse Apex\\App\\Interfaces\\Opus\\DataTableInterface;\n",
            $code
        );

        // Update formatRow() method
        $code = str_replace(
            "        // Format row\n",
            base64_decode('ICAgICAgICAvLyBHZXQgdXNlcgogICAgICAgIGlmICghJHVzZXIgPSBVc2VyOjpsb2FkVXVpZCgkcm93Wyd1dWlkJ10pKSB7CiAgICAgICAgICAgIHRocm93IG5ldyBVc2VyTm90RXhpc3RzRXhjZXB0aW9uKCJObyB1c2VyIGV4aXN0cyBpbiB0aGUgZGF0YWJhc2Ugd2l0aCB0aGUgdXVpZCwgJHJvd1t1dWlkXSIpOwogICAgICAgIH0KCiAgICAgICAgLy8gRm9ybWF0IHJvdwogICAgICAgICRyb3dbJ3VzZXInXSA9ICR1c2VyLT5nZXRVc2VybmFtZSgpIC4gJyAoJyAuICR1c2VyLT5nZXRGdWxsTmFtZSgpIC4gJyknOwo='),
            $code
        );

        // Save code
        return $code;
    }

    /**
     * Add hash
     */
    public function addHash(string $col_name, string $hash_alias, string $table_class, string $code):string
    {

        // Add use declaration, if needed
        if (!str_contains($code, "use Apex\\App\\Sys\\Utils\\Hashes")) {
            $code = str_replace(
                "use Apex\\App\\Interfaces\\Opus\\DataTableInterface;\n",
                "use Apex\\App\\Sys\\Utils\\Hashes;\nuse Apex\\App\\Interfaces\\Opus\\DataTableInterface;\n",
                $code
            );

            // Add to  __construct() method
            $code = str_replace(
                "        private Db \$db,\n",
                "        private Db \$db,\n        private Hashes \$hashes,\n",
                $code
            );
        }

        // Update formatRow() method
        $code = str_replace(
            "        // Format row\n",
            "        // Format row\n        \$row['$col_name'] = \$this->hashes->getVar('$hash_alias', \$row['$col_name']);\n",
            $code
        );

        // Save and return
        return $code;
    }

    /**
     * Add forin key
     */
    public function addForeignKey(string $col_name, string $dbtable, string $table_class, string $code):string
    {

        // Get item desc
        list($item_desc, $sort_by) = $this->getItemDescription($dbtable, true);
        $fcol_alias = "\$" . preg_replace("/_id$/", "", $col_name) . "_row";
        $frow_sql = "        $fcol_alias = \$this->db->getIdRow('$dbtable', \$row['$col_name']);\n"; 

        // Format item desc
        preg_match_all("/~(.+?)~/", $item_desc, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            $var = $fcol_alias . "[" . $m[1] . "]";
            $item_desc = str_replace($m[0], $var, $item_desc);
        }

        // Update formatRow() method
        $code = str_replace(
            "\n        // Format row\n",
            "\n$frow_sql\n        // Format row\n        \$row['$col_name'] = \"$item_desc\";\n",
            $code
        );

        // Save and return
        return $code;
    }

}


