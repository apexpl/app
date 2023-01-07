<?php

namespace Apex\App\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Router interface
 */
interface RouterInterface
{

    /**
     * Lookup a route
     */
    public function lookup(ServerRequestInterface $request):RouterResponseInterface;

}


