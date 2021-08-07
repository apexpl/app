<?php
declare(strict_types = 1);

namespace Apex\Svc;

use Apex\Svc\{App, Di};
use Apex\Cluster\Router\Validator;
use Apex\Cluster\Interfaces\MessageRequestInterface;
use Apex\Cluster\Exceptions\ClusterInvalidRoutingKeyException;

/**
 *Message Request model.
 */
class MessageRequest implements MessageRequestInterface 
{

    // Properties
    private string $type = 'rpc';
    private string $instance_name = '';
    private array $caller;

    /**
     * Constructor
     */
    public function __construct(
        private string $routing_key, 
        ...$params
    ) {

        // Set params
        $this->params = $params;

        // Get caller function / class
        $trace = debug_backtrace();
        $this->caller = array(
            'file' => $trace[0]['file'] ?? '',  
            'line' => $trace[0]['line'] ?? 0,
            'function' => $trace[1]['function'] ?? '',
            'class' => $trace[1]['class'] ?? ''
        );

        // Parse routing key
        if (!preg_match("/^(\w+?)\.(\w+?)\.(\w+)$/", strtolower($routing_key), $match)) { 
            throw new ClusterInvalidRoutingKeyException("Invalid routing key, $routing_key.  Must be formatted as x.y.z");
        }

    }

    /**
     * Set instance name
     */
    public function setInstanceName(string $name):void
    {
        $this->instance_name = $name;
    }

    /**
     * Set the message type. 
     */
    public function setType(string $type):void
    {
        Validator::validateMsgType($type);
        $this->type = $type; 
    }

    /**
     * Get instance name
     */
    public function getInstanceName():string { 
        return $this->instance_name; 
    }

    /**
     * Get the message type. 
     */
    public function getType():string { 
        return $this->type; 
    }

    /**
     * Get the routing key 
     */
    public function getRoutingKey():string { 
        return $this->routing_key; 
    }

    /**
     * Get the caller array. 
     */
    public function getCaller():array 
    { 
        return $this->caller; 
    }

    /**
     * Get request
     */
    public function getRequest():mixed 
    { 
        $app = Di::get(App::class);
        return $app;
    }

    /**
     * Get the params of the request. 
     */
    public function getParams():mixed
    { 
        return count($this->params) == 1 ? $this->params[0] : $this->params;
    }


}

