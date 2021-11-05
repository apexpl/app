<?php
declare(strict_types = 1);

namespace Apex\App\Base\Router;

use Apex\App\Interfaces\RouterResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Apex\App\Attr\Inject;

/**
 * Router response
 */
class RouterResponse implements RouterResponseInterface
{

    /**
     * Constructor
     */
    public function __construct(
        private MiddlewareInterface $http_controller, 
        private string $path_translated, 
        private array $params
    ) { 

    }

    /**
     * Get http controller
     */
    public function getMiddleware():MiddlewareInterface
    {
        return $this->http_controller;
    }

    /**
     * Get params
     */
    public function getPathParams():array
    {
        return $this->params;
    }

    /**
     * Get path translated
     */
    public function getPathTranslated():string
    {
        return $this->path_translated;
    }

    /**
     * With middleware
     */
    public function withMiddleware(MiddlewareInterface $http_controller):static
    {

        return new RouterResponse(
            http_controller: $http_controller, 
            path_translated: $this->path_translated, 
            params: $this->params
        );

    }

    /**
     * With path params
     */
    public function withPathParams(array $params):static
    {

        return new RouterResponse(
            http_controller: $this->http_controller, 
            path_translated: $this->path_translated, 
            params: $params
        );

    }

    /**
     * With pth translated
     */
    public function withPathTranslated(string $path):static
    {

        return new RouterResponse(
            http_controller: $this->http_controller, 
            path_translated: $path, 
            params: $this->params
        );

    }

}

