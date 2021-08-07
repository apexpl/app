<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\App\Sys\Utils\Hashes;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Interfaces\TagInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_select implements TagInterface
{

    #[Inject(Hashes::class)]
    private Hashes $hashes;

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Initialize
        $required = $e->getAttr('required') ?? 0;
        $value = $e->getAttr('value') ?? '';
        $width = $e->getAttr('width') ?? '';
        $onchange = $e->getAttr('onchange') ?? '';

        // Set replace vars
        $replace = [
            '~onchange~' => $onchange != '' ? 'onchange="' . $onchange . '"' : '', 
            '~width~' => $width != '' ? 'style="width: ' . $width . ';"' : ''
        ];

        // Get select options
        $options = $required == 1 ? '' : '<option value="">------------</option>';
        if (($data_source = $e->getAttr('data_source')) !== null) { 
            $options .= $this->hashes->parseDataSource($data_source, $value, 'select');
        } else { 
            $options .= $e->getBody();
        }
        $replace['~options~'] = $options;

        // Return
        return strtr($html, $replace);
    }

}


