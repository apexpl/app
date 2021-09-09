<?php
declare(strict_types = 1);

namespace Apex\App\Sys\Boot;

/**
 * Request inputs
 */
class RequestInputs
{

    // Properties
    protected array $_config;
    protected array $inputs;

    /**
     * $_POST
     */
    public function post(string $key, $default = null)
    {
        return array_key_exists($key, $this->inputs['post']) ? $this->inputs['post'][$key] : $default; 
    }

    /**
     * $_GET
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->inputs['get']) ? $this->inputs['get'][$key] : $default; 
    }

    /**
     * $_REQUEST
     */
    public function request(string $key, $default = null)
    {

        $value = match(true) { 
            array_key_exists($key, $this->inputs['post']) ? true : false => $this->inputs['post'][$key], 
            array_key_exists($key, $this->inputs['get']) ? true : false => $this->inputs['get'][$key], 
            default => $default
        };
        return $value;
    }

    /**
     * $_SERVER
     */
    public function server(string $key, $default = null)
    {
        return array_key_exists($key, $this->inputs['server']) ? $this->inputs['server'][$key] : $default; 
    }

    /**
     * $_COOKIE
     */
    public function cookie(string $key, $default = null)
    {
        return array_key_exists($key, $this->inputs['cookie']) ? $this->inputs['cookie'][$key] : $default; 
    }

    /**
     * Path param
     */
    public function pathParam(string $key, $default = null)
    {
        return array_key_exists($key, $this->inputs['path_params']) ? $this->inputs['path_params'][$key] : $default; 
    
}
    /**
     * Config var
     */
    public function config(string $key, $default = null)
    {

        if (array_key_exists($key, $this->_config)) { 
            return $this->_config[$key];
        } elseif (!preg_match("/^(.+?)\.(.+)$/", $key, $m)) { 
            return $default;
        }

        return array_key_exists($key, $this->_config) ? $this->_config[$key] : $default;
    }

    /**
     * Has $_POST
     */
    public function hasPost(string $key):bool
    {
        return array_key_exists($key, $this->inputs['post']);
    }

    /**
     * Has $_GET
     */
    public function hasGet(string $key):bool
    {
        return array_key_exists($key, $this->inputs['get']); 
    }

    /**
     * Has $_REQUEST
     */
    public function hasRequest(string $key):bool
    {
        return (array_key_exists($key, $this->inputs['post']) || array_key_exists($key, $this->inputs['get'])) ? true : false;
    }

    /**
     * Has $_SERVER
     */
    public function hasServer(string $key):bool
    {
        return array_key_exists($key, $this->inputs['server']); 
    }

    /**
     * Has $_COOKIE
     */
    public function hasCookie(string $key):bool
    {
        return array_key_exists($key, $this->inputs['cookie']); 
    }

    /**
     * Has config var
     */
    public function hasConfig(string $key):bool
    {
        return array_key_exists($key, $this->_config);
    }

    /**
     * Has path param
     */
    public function hasPathParam(string $key):bool
    {
        return array_key_exists($key, $this->inputs['path_param']); 
    }

    /**
     * Get all $_POST
     */
    public function getAllPost():array
    {
        return $this->inputs['post'];
    }

    /**
     * Get all $_GET
     */
    public function getAllGet():array
    {
        return $this->inputs['get'];
    }

    /**
     * Get all $_REQUEST
     */
    public function getAllRequest():array
    {
        return array_merge($this->inputs['post'], $this->inputs['get']);
    }

    /**
     * Get all $_SERVER
     */
    public function getAllServer():array
    {
        return $this->inputs['server'];
    }

    /**
     * Get all $_COOKIE
     */
    public function getAllCookie():array
    {
        return $this->inputs['cookie'];
    }

    /**
     * Get all path params
     */
    public function getAllPathParams():array
    {
        return $this->inputs['path_params'];
    }

    /**
     * Get all config vars
     */
    public function getAllConfig():array
    {
        return $this->_config;
    }

    /**
     * Clear $_POST
     */
    public function clearPost():void
    {
        $this->inputs['post'] = [];
    }

    /**
     * Clear $_GET
     */
    public function clearGet():void
    {
        $this->inputs['get'] = [];
    }

    /**
     * Clear $_POST and $_GET
     */
    public function clearPostGet():void
    {
        $this->inputs['post'] = [];
        $this->inputs['get'] = [];
    }

    /**
     * Clear $_COOKIE
     */
    public function clearCookie():void
    {
        $this->inputs['cookie'] = [];
    }

    /**
     * Replace $_POST
     */
    public function replacePost(array $values):void
    {
        $this->inputs['post'] = $values;
    }

    /**
     * Replace $_GET
     */
    public function replaceGet(array $values):void
    {
        $this->inputs['get'] = $values;
    }

    /**
     * Replace $_SERVER
     */
    public function replaceServer(array $values):void
    {
        $this->inputs['server'] = $values;
    }

    /**
     * Replace $_COOKIE
     */
    public function replaceCookie(array $values):void
    {
        $this->inputs['cookie'] = $values;
    }

    /**
     * Replace path params
     */
    public function replacePathParams(array $values):void
    {
        $this->inputs['path_params'] = $values;
    }

}


