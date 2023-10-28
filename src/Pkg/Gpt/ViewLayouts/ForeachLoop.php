<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\ViewLayouts;

use Apex\App\Pkg\Gpt\Models\ViewInfoModel;

/**
 * foreahc Loop
 */
class ForeachLoop extends AbstractLayout
{

    /**
     * Get body HTML - foreach loop
     */
    public function generateHtml(ViewInfoModel $info)
    {

        // Initialize
        $cols = $this->db->getColumnDetails($info->dbtable);
        $prefix = '~' . $info->alias . '.';

        // Get item name
        $item_title = match(true) {
            isset($cols['title']) => 'ID# ' . $prefix . 'id~ - ' . $prefix . 'title~',
            isset($cols['name']) => 'ID# ' . $prefix . 'id~ - ' . $prefix . 'name~',
            default => 'ID# ' . $prefix . 'id~'
        };

        // Set html
        $html = "<s:foreach name=\"$info->alias\">\n\n";
        $html .= "    <s:box>\n";
        $html .= "        <s:box_header title=\"$item_title\">\n";
        $html .= "        </s:box_header>\n\n";

        // Go through columns
        foreach ($cols as $col_name => $vars) {
            $name = $this->convert->case($col_name, 'phrase');
            $html .= "        <b>$name:</b> " . $prefix . $col_name . "~<br />\n";
        }

        // Add button
        $uri = '/' . preg_replace("/^public\//", "", $info->uri) . '/' .$prefix . 'id~';
        $html .= "    <br />\n\n";
        $html .= "    <button href=\"$uri\" style=\"padding: 20px; text-align: right;\" class=\"btm btm-primary btm-md\">View Details</button>\n\n";

        // Finish
        $html .= "    </s:box><br />\n";
        $html .= "</s:foreach>\n\n";

        // Save html
        $this->saveHtml($info, $html);
    }

    /**
     * Get PHP code
     */
    public function generatePhp(ViewInfoModel $info):void
    {

        // GEt model class
        $model_class = $this->getModelByTable($info->pkg_alias, $info->dbtable);
        $obj = new \ReflectionClass($model_class);

        // Set use declarations
        $use = [
            "use Apex\\Svc\\{App, View};",
            "use " . $model_class . ";"
        ];

        // get() method
        $code = "    /**\n     * Get\n     **/\n";
        $code .= "    public function get(View \$view):void\n    {\n\n";
        $code .= "        // Go through all items\n";
        $code .= "        \$items = " . $obj->getShortName() . "::all('id', 'desc');\n";
        $code .= "        foreach (\$items as \$item) {\n";
        $code .= "            \$view->addBlock('$info->alias', \$item->toDisplayArray());\n";
        $code .= "        }\n    }\n\n";

        // Save PHP code
        $this->savePhp($info, $code, $use);
    }

}


