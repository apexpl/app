<?php
declare(strict_types=1);

namespace Apex\App\Pkg\Gpt\ViewLayouts;

use Apex\App\Pkg\Gpt\Models\ViewInfoModel;

/**
 * Blank
 */
class SingleForm extends AbstractLayout
{

    /**
     * Get body HTML - foreach loop
     */
    public function generateHtml(ViewInfoModel $info)
    {
        $this->saveHtml($info, 'SingleForm');
    }

    /**
     * Get PHP code
     */
    public function generatePhp(ViewInfoModel $info):void
    {
        $this->savePhp($info, '', []);
    }

}


