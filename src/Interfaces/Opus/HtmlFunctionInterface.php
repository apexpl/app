<?php

namespace Apex\App\Interfaces\Opus;

use Apex\Syrus\Parser\StackElement;

/**
 * HTML Function Interface
 */
interface HtmlFunctionInterface
{

    /**
     * Render
     */
    public function render(string $html, StackElement $e):string;

}

