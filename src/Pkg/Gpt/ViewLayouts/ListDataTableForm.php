<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\ViewLayouts;

use Apex\App\Pkg\Gpt\Models\ViewInfoModel;
use Apex\Opus\Builders\CrudBuilder;

/**
 * Blank
 */
class ListDataTableForm extends AbstractLayout
{

    #[Inject(CrudBuilder::class)]
    private CrudBuilder $crud_builder;

    /**
     * Get body HTML - foreach loop
     */
    public function generateHtml(ViewInfoModel $info)
    {

        // Initialize
        $alias_plural = $this->crud_builder->applyFilter($info->alias, 'plural');
        if (!str_ends_with($alias_plural, 's')) {
            $alias_plural .= 's';
        }
        $table_alias = $info->pkg_alias . '.' . strtolower($alias_plural);

        // Set html
        $html = "<s:box>\n";
        $html .= "    <s:box_header title=\"" . $this->convert->case($alias_plural, 'phrase') . "\">\n";
        $html .= "        <p>The below table lists all " . strtolower($alias_plural) . " which you may view and manage as desired.</p>\n";
        $html .= "    </s:box_header>\n\n";
        $html .= "    <s:function alias=\"display_table\" table=\"$table_alias\">\n";
        $html .= "</s:box>\n\n";

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


