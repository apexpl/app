<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt;

use Apex\App\Pkg\Helpers\PackageConfig;

/**
 * GPT Model
 */
class GptModel extends GptClient
{

    #[Inject(PackageConfig::class)]
    private PackageConfig $pkg_config;

    /**
     * Add toDisplayArray()
     */
    public function addToDisplayArray(string $class_name):void
    {

        // GEt table name
        $obj = new \ReflectionClass($class_name);
        if (!$prop = $obj->getProperty('dbtable')) {
            return;
        }
        $dbtable = $prop->getValue($obj);

        // Get pkg_alias
        preg_match("/App\\\\(.+?)\\\\/", $class_name, $match);
        $pkg_alias = $this->convert->case($match[1], 'lower');

        // Get hashes
        $yaml = $this->pkg_config->load($pkg_alias);
        $hashes = isset($yaml['hashes']) && is_array($yaml['hashes']) ? array_keys($yaml['hashes']) : [];

        // Get columns
        $cols = $this->db->getColumnDetails($dbtable);

        // Initialize
        $fkeys = $this->db->getForeignKeys($dbtable);

        // Initialize
        $get_objects = [];
        $use_declarations = [];

        // Start php code
        $code = "    /**\n     * toDisplayArray\n     */\n";
        $code .= "    public function toDisplayArray():array\n    {\n\n";
        $code .= "        // Get vars\n        \$vars = \$this->toArray();\n\n";
        $code .= "        // Format\n";

        // Go through properties
        foreach ($obj->getProperties() as $prop) {
            $col_name = $prop->getName();
            $hash_alias = str_replace(str_replace('-', '_', $pkg_alias) . '_', '', $dbtable) . '_' . $col_name;

            // Initial checks
            if ($prop->hasType() && $prop->getType()::class == 'ReflectionUnionType') {
                continue;
            } elseif ($prop->hasType() && !$name = $prop->getType()->getName()) {
                continue;
            }

            // Process property type
            if ($name == 'DateTime') {
                $code .= "        \$vars['$col_name'] = \$this->convert->date(\$vars['$col_name'], true);\n";
            } elseif ($name == 'bool') {
                $code .= "        \$vars['$col_name'] = \$this->$col_name === true ? 'Yes' : 'No';\n";
            } elseif ($name == 'float' && preg_match("/(amount|cost|price|fee|tax|tip|commission)/", $col_name)) {
                $code .= "        \$vars['$col_name'] = \$this->convert->money(\$vars['$col_name']);\n";
            } elseif (enum_exists($name)) {
                $code .= "        \$vars['$col_name'] = \$this->$col_name->value;\n";
            } elseif (in_array($hash_alias, $hashes)) {
                $hash_alias = $pkg_alias . '.' . $hash_alias;
                $code .= "        \$vars['$col_name'] = \$this->hashes->getVar('$hash_alias', \$vars['$col_name']);\n";
                $use_declarations["use Apex\\App\\Sys\\Utils\\Hashes;"] = "    #[Inject(Hashes::class)]\n    protected Hashes \$hashes;\n";
            } elseif (isset($fkeys[$col_name]) && list($get_line, $format_line) = $this->getForeignKey($col_name, $fkeys[$col_name]['table'], $obj)) {
                $code .= "$format_line\n";
                $get_objects[] = $get_line;
            }
        }

        // Finish
        $code .= "\n        // Return\n        return \$vars;\n    }\n\n";

        // Compile code
        $this->compileClassCode($obj->getFilename(), $code, $use_declarations, $get_objects);
    }

    /**
     * Get foreign key lines
     */
    private function getForeignKey(string $col_name, string $dbtable, \ReflectionClass $obj):?array
    {

        // Initial checks
        if ($col_name == 'uuid') {
            return null;
        }

        // Get item desc
        list($item_desc, $sort_by) = $this->getItemDescription($dbtable, true);
        $col_alias = preg_replace("/_id$/", "", $col_name);

        // Check for method
        $method_name = 'get' . $this->convert->case($col_alias, 'title'); 
        if (!$obj->getMethod($method_name)) {
            return null;
        }

        // Format item desc
        preg_match_all("/~(.+?)~/", $item_desc, $match, PREG_SET_ORDER);
        foreach ($match as $m) {
            $var = "\$" . $col_alias . "->" . $m[1];
            $item_desc = str_replace($m[0], $var, $item_desc);
        }

        // Set variables
        $get_line = "        \$" . $col_alias . " = " . $this->convert->case($col_alias, 'title') . "whereId::();";
        $format_line = "        \$vars['$col_name'] = \"$item_desc\";";

        // Return
        return [$get_line, $format_line];
    }

    /**
     * Compile class code
     */
    public function compileClassCode(string $filename, string $method_code, array $use_declarations, array $get_objects):void
    {

        // Get code
        $code = file_get_contents($filename);

        // Add use declarations
        preg_match("/\nuse(.*?)\n\n/si", $code, $match);
        $use_dec = "\nuse " . $match[1];
        foreach ($use_declarations as $nm => $inject) {
            if (!str_contains($use_dec, $nm)) {
                $use_dec .= "\n$nm\n";
            }
        }
        $code = str_replace($match[0], "$use_dec\n\n", $code);
        $code = preg_replace("/\nclass (.+?)\n{\n\n/", "\nclass $1\n{\n\n" . implode("\n", array_values($use_declarations)), $code);

        // Add get objects
        if (count($get_objects) > 0) {
            $get_code = "        // Get needed objects\n        ";
            $get_code .= implode("\n        ", $get_objects) . "\n";
            $method_code = str_replace("        //Format\n", $get_code, $method_code);
        }

        // Add method code
        $code = rtrim(trim($code), "}");
        $code .= "\n$method_code\n}\n";

        // Save file
        file_put_contents($filename, $code);
    }

}


