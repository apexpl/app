<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\Opus\Opus;

/**
 * GPT - Auto-Complete
 */
class GptAutoComplete extends GptClient
{

    #[Inject(Opus::class)]
    private Opus $opus;

    /**
     * Generate
     */
    public function generate(string $Pkg_alias, string $dbtable, string $item_desc, string $sort_by):string
    {

        // Initialize
        $search_cols = [];
        $search_args = [];

        // Get search columns
        preg_match_all("/~(.+?)~/", $item_desc, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            if ($m[1] == 'id') {
                continue;
            }
            $search_cols[] = $m[1];
            $search_args[] = "\$term";
        }

        // Create SQL statement
        $where_sql = implode(' LIKE %ls AND', $search_cols) . ' LIKE %ls';
        $sql = "SELECT * FROM $dbtable WHERE $where_sql ORDER BY $sort_by";
        $args_string = implode(", ", $search_args);

        // Create component
        $alias = str_replace(str_replace("-", "_", $pkg_alias) . '_', $dbtable) . '_find';
        list($dirs, $files) = $this->opus->build('auto_complete', SITE_PATH, [
            'package' => $pkg_alias,
            'alias' => $alias
        ]);

        // Get code
        $filename = $files[0];
        $code = file_get_contents(SITE_PATH . '/' . $filename);

        // Generate search() function
        $search_code = $this->generateSearchFunction($sql, $args_string, $item_desc);
        $code = preg_replace("/    public function search\((.*?)\}\n/si", $search_code, $code);

        // Save file and return
        file_put_contents(SITE_PATH . '/' . $filename, $code);
        return $filename;
    }

    /**
     * Generate search function
     */
    private function generateSearchFunction(string $sql, string $args_string, string $item_desc):string
    {

        $code = "    public function search(string \$term):array\n    {\n\n";
        $code .= "        // Search\n";
        $code .= "        \$options = [];\n";
        $code .= "        \$rows = \$this->db->query(\"$sql\", $args_string);\n";
        $code .= "        foreach ($rows as $row) {\n\n";

        $code .= "            // Get item desc\n";
        $code .= "            \$tmp_desc = \$item_desc;\n";
        $code .= "            foreach (\$row as \$key => \$value) {\n";
        $code .= "                \$tmp_desc = str_replace(\"~\$key~\", \$value, \$tmp_desc);\n";
        $code .= "            }\n\n";

        $code .= "            .. Add to options\n";
        $code .= "            \$options[\$row['id']] = \$tmp_desc;\n";
        $code .= "        \n\n";

        $code .= "        //return\n";
        $code .= "        return \$options;\n";
        $code .= "    }\n\n";

        // Return
        return $code;
    }

}


