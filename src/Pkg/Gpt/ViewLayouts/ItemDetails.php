<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\ViewLayouts;

use Apex\App\Pkg\Gpt\Models\ViewInfoModel;
use Apex\Opus\Builders\CrudBuilder;

/**
 * Blank
 */
class ItemDetails extends AbstractLayout
{

    #[Inject(CrudBuilder::class)]
    private CrudBuilder $crud_builder;

    /**
     * Get body HTML - foreach loop
     */
    public function generateHtml(ViewInfoModel $info)
    {

        // Initialize
        $alias_single = $this->crud_builder->applyFilter($info->alias, 'single');

        // Set html
        $html = "<s:box>\n";
        $html .= "    <s:box_header title=\"" . $this->convert->case($alias_single, 'phrase') . " Details\">\n";
        $html .= "        <p>Below lists all information on the selected " . strtolower($alias_single) . ".</p>\n";
        $Html .= "    </s:box_header>\n\n";
        $html .= "        <s:form_table>\n";
        $html .= "    <s:foreach name=\"" . strtolower($alias_single) . "\">\n";

        // GO through items
        $cols = $this->db->getColumnDetails($info->dbtable);
        foreach ($cols as $col_name => $vars) {
            $html .= "        <s:ft_label label=\"$name\" value=\"~" . strtolower($alias_single) . "." . $col_name . "~\">\n";
        }
        $html .= "    </s:foreach>\n    </s:form_table>\n\n</s:box>\n\n";

        // Save html
        $this->saveHtml($info, $html);
    }

    /**
     * Get PHP code
     */
    public function generatePhp(ViewInfoModel $info):void
    {
        $this->savePhp($info, '', []);
    }

}


