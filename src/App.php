<?php
declare(strict_types = 1);

namespace Apex\App;

use Apex\App\Sys\Boot\Bootloader;
use Apex\App\Sys\ClientInfo;
use Apex\App\Interfaces\RouterInterface;
use Apex\Armor\Interfaces\ArmorUserInterface;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface, UriInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Apex\Container\Interfaces\ApexContainerInterface;
use Apex\Armor\Auth\AuthSession;
use Symfony\Component\Yaml\Yaml;
use Apex\App\Exceptions\ApexYamlException;


/**
 * Central app class for Apex.
 */
class App extends Bootloader implements RequestHandlerInterface
{

    /**
     * Constructor
     */
    public function __construct()
    {

        // Load request via Bootloader class
        $this->bootload();
        $this->cntr->set(__CLASS__, $this);

    }

    /**
     * Get container
     */
    public function getContainer():ApexContainerInterface
    {
        return $this->cntr;
    }

    /**
     * Get sever request
     */
    public function getRequest():ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Get client
     */
    public function getClient():ClientInfo
    {
        return $this->client;
    }

    /**
     * Handle request
     */
    public function handle(ServerRequestInterface $request):ResponseInterface
    {

        // Get route
        $router = $this->cntr->make(RouterInterface::class);
        $res = $router->lookup($this->request);
        $this->path = $res->getPathTranslated();

        // Set variables
        $this->replacePathParams($res->getPathParams());
        $controller = $res->getMiddleWare();

        // Process request
        $response = $controller->process($request, $this);

        // Return
        return $response;
    }

    /**
     * Output response
     */
    public function outputResponse(ResponseInterface $response)
    {

        // Set status
        http_response_code($response->getStatusCode());

        // Set headers
        $headers = $response->getHeaders();
        foreach ($headers as $key => $values) { 
            $line = $key . ': ' . $response->getHeaderLine($key);
            header($line);
        }

        // Send body
        echo $response->getBody();
    }

    /**
     * Get path
     */
    public function getPath():string
    {
        return $this->path;
    }

    /**
     * Get path original
     */
    public function getPathOriginal():string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Get host
     */
    public function getHost():string
    {
        return $this->request->getUri()->getHost();
    }

    /**
     * Get port
     */
    public function getPort():int
    {
        return $this->request->getUri()->getPort();
    }

    /**
     * get method
     */
    public function getMethod():string
    {
        return $this->request->getMethod();
    }

    /**
     * Get content type
     */
    public function getContentType():string
    {
        return $this->content_type;
    }

    /**
     * Get uri interface
     */
    public function getUri():UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * Is auth
     */
    public function isAuth():bool
    {
        return $this->client->getUuid() == '' ? false : true;
    }

    /**
     * Get uuid
     */
    public function getUuid():string
    {
        return $this->client->getUuid();
    }

    /**
     * Get auth session
     */
    public function getSession():?AuthSession
    {
        return $this->session;
    }

    /**
     * Get user
     */
    public function getUser():?armorUserInterface
    {

        // Check for session
        if ($this->user === null && $this->session !== null) { 
            $this->user = $session->getUser();
        }

        // Return
        return $this->user;
    }

    /**
     * Get area
     */
    public function getArea():string
    {
        return $this->client->getArea();
    }

    /**
     * Get action
     */
    public function getAction():string
    {
        return $this->post('submit', '');
    }

    /**
     * Set path
     */
    public function setPath(string $path, bool $is_locked = false):bool
    {

        // Check if locked
        if ($this->path_is_locked === true) { 
            return false;
        }
        $this->path = $path;
        $this->path_is_locked = $is_locked;
    }

    /**
     * Set content type
     */
    public function setContentType(string $type):void
    {
        $this->content_type = $type;
    }

    /**
     * Set uuid
     */
    public function setUuid(string $uuid):void
    {
        $this->client->setUuid($uuid);
    }

    /**
     * Set auth session
     */
    public function setSession(AuthSession $session):void
    {
        $this->session = $session;
        $this->setUuid($session->getUuid());
    }

    /**
     * Set user
     */
    public function setUser(ArmorUserInterface $user):void
    {
        $this->user = $user;
        $this->setUuid($user->getUuid());
    }

    /**
     * Set area
     */
    public function setArea(string $area):void
    {
        $this->client->setArea($area);
    }

    /**
     * Get routes config
     */
    public function getRoutesConfig(string $filename = 'routes.yml'):array
    {

        // If already loaded
        if (isset($this->boot_config[$filename])) { 
            return $this->boot_config[$filename];
        }

        // Load Yaml file
        try {
            $yaml = Yaml::parseFile(SITE_PATH . '/boot/' . $filename);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) { 
            throw new ApexYamlException("Unable to parse /boot/$filename YAML file, error: " . $e->getMessage());
        }

        // Set and return
        $this->boot_config[$filename] = $yaml;
        return $yaml;
    }

}

