<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\ViewLayouts;

use Apex\App\Pkg\Gpt\Models\ViewInfoModel;
use Apex\App\Pkg\Gpt\GptClient;

/**
 * Abstract layout
 */
abstract class AbstractLayout extends GPtClient
{

    /**
     * Compile html
     */
    public function saveHtml(ViewInfoModel $info, string $body):void
    {

        // Initialize
        $cols = $this->db->getColumnDetails($info->dbtable);

        // Get title
        if (preg_match("/(create|update|edit|view|delete)/", $info->alias, $match)) {
            $tbl_name = str_replace($pkg_alias . '_', '', $dbtable);
            $title = ucwords($match[1]) . ' ' . $this->convert->case($tbl_alias, 'phrase');
        } else { 
            $title = 'Manage ' . $this->convert->case($info->alias, 'phrase');
        }

        // Start html
        $html = "\n<h1>$title</h1>\n\n";
        $html .= "<p>$info->description</p>\n\n";
        $html .= "<s:form>\n\n";

        // Add body
            $html .= "$body\n\n";

        // Save and return
        $filename = str_replace("/php/", "/html/", preg_replace("/\.php$/", ".html", $info->files[0]));
        file_put_contents(SITE_PATH . '/' . $filename, $html);
    }

    /**
     * Save PHP code
     */
    public function savePhp(ViewInfoModel $info, string $method_code, array $use_declarations):void
    {

        // Start PHP
        $php = "<?php\ndeclare(strict_Types=1);\n\n";

        // Add namespace
        $namespace = $info->parent_namespace == '' ? "Views" : "Views\\" . $info->parent_namespace;
        $php .= "namespace " . $namespace . ";\n\n";
        $php .= implode("\n", $use_declarations) . "\n\n";
        $php .= "/**\n * $info->alias View\n */\n";
        $php .= "class $info->alias\n{\n\n";
        $php .= $method_code . "}\n\n";

        // Save file
        $filename = str_replace("/html/", "/php/", preg_replace("/\.html$/", ".php", $info->files[0]));
        file_put_contents(SITE_PATH . '/' . $filename, $php);
    }

}


