<?php

namespace Apex\App\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Router response interface
 */
interface RouterResponseInterface
{

    /**
     * Get middleware
     */
    public function getMiddleware():MiddlewareInterface;

    /**
     * With Middleware
     */
    public function withMiddleware(MiddlewareInterface $middleware):static;

    /**
     * Get path params
     */
    public function getPathParams():array;

    /**
     * With path params
     */
    public function withPathParams(array $params):static;

    /**
     * Get translated path
     */
    public function getPathTranslated():string;

    /**
     * With path translated
     */
    public function withPathTranslated(string $path):static;

}


