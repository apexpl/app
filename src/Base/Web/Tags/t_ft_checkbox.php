<?php
declare(strict_types = 1);

namespace Apex\App\Base\Web\Tags;

use Apex\App\Sys\Utils\Hashes;
use Apex\Syrus\Parser\StackElement;
use Apex\Syrus\Render\Tags;
use Apex\Syrus\Interfaces\TagInterface;

/**
 * Renders a specific template tag.  Please see developer documentation for details.
 */
class t_ft_checkbox implements TagInterface
{

    #[Inject(Hashes::class)]
    private Hashes $hashes;

    #[Inject(Tags::class)]
    private Tags $tags;
    /**
     * Render
     */
    public function render(string $html, StackElement $e):string
    {

        // Initialize
        $attr = $e->getAttrAll();
        $value = explode(',', ($e->getAttr('value') ?? ''));
        if (!isset($attr['label'])) { 
            $attr['label'] = ucwords(str_replace('_', ' ', $attr['name']));
        }

        // Get select options
        if (($data_source = $e->getAttr('data_source')) !== null) { 
            $options = $this->hashes->parseDataSource($data_source, $value, 'checkbox', $attr['name']);
        } else { 
            $options .= $e->getBody();
        }

        // Return
        return $this->tags->getSnippet('ft_twocol', $options, $attr);
    }

}


