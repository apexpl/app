<?php
declare(strict_types = 1);

namespace Apex\App\Adapters;

use Apex\Svc\{App, Convert, Di};
use Apex\Syrus\Render\Templates;
use Apex\Cluster\Interfaces\{MessageRequestInterface, FeHandlerInterface};
use Apex\App\Attr\Inject;
use redis;

/**
 * Cluster router
 */
class ClusterAdapter
{

    /**
     * Constructor
     */
    public function __construct(
        private Convert $convert,
        private redis $redis
    ) { 

    }

    /**
     * Determine route
     */
    public function lookupRoute(MessageRequestInterface $msg)
    {

        // Get routing key
        $parts = array_map(fn ($part) => $this->convert->case($part, 'lower'), explode('.', $msg->getRoutingKey()));
        array_pop($parts);

        // Check redis
        $routing_key = implode('.', $parts);
        $classes = $this->redis->hgetall('config:listeners:' . $routing_key) ?? [];

        // Return
        return $classes; 
    }

    /**
     * Prepare message for listener
     */
    public function prepareMessage(MessageRequestInterface $msg)
    {
        $app = Di::get(App::class);
        $app->setRequest($msg->getRequest());
    }

    /**
     * Timeout
     */
    public function timeout(MessageRequestInterface $msg):void
    {
        echo "Timed out from cluster, needs to be implemented."; exit;
    }

    /**
     * Front-end handler
     */
    public function handleFrontEndCallback(FeHandlerInterface $handler):void
    {
        $templates = Di::make(Templates::class);
        $templates->handleClusterResponse($handler);
    }


}

