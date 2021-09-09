<?php

namespace Apex\App\Interfaces\Opus;

/**
 * Auto complete interface
 */
interface AutoCompleteInterface
{

    /**
     * Search options.
     *
     * @return Associative array of options to display within the auto-complete list.
     */
    public function search(string $term):array;

}




