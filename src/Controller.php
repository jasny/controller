<?php

namespace Jasny;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller
 */
abstract class Controller
{
    /**
     * Server request
     * @var ServerRequestInterface
     **/
    protected $request = null;

    /**
     * Response
     * @var ResponseInterface
     **/
    protected $response = null;

    /**
     * Flash
     * @var Flash
     **/
    protected $flash = null;

    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    abstract public function run();

    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Run the controller as function
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;

        return $this->run();
    }

    /**
     * Set the flash message and/or return the flash object.
     * 
     * @param mixed $type     flash type, eg. 'error', 'notice' or 'success'
     * @param mixed $message  flash message
     * @return Flash
     */
    public function flash($type = null, $message = null)
    {
        if (!isset($this->flash)) $this->flash = new Flash();        
        if ($type && $message) $this->flash->set($type, $message);

        return $this->flash;
    }

    /**
     * Check if response is 2xx succesful, or empty
     * 
     * @return boolean
     */
    public function isSuccessful()
    {
        $code = $this->getResponseStatusCode();

        return !$code || ($code >= 200 && $code < 300);
    }
    
    /**
     * Check if response is a 3xx redirect
     * 
     * @return boolean
     */
    public function isRedirection()
    {
        $code = $this->getResponseStatusCode();

        return $code >= 300 && $code < 400;
    }
    
    /**
     * Check if response is a 4xx client error
     * 
     * @return boolean
     */
    public function isClientError()
    {
        $code = $this->getResponseStatusCode();

        return $code >= 400 && $code < 500;
    }
    
    /**
     * Check if response is a 5xx redirect
     * 
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getResponseStatusCode() >= 500;
    }   

    /**
      * Check if response is 4xx or 5xx error
      *
      * @return boolean
      */
     public function isError()
     {
         return $this->isClientError() || $this->isServerError();
     } 

     /**
      * Get status code of response
      *
      * @return int
      */
     protected function getResponseStatusCode()
     {
        $response = $this->getResponse();

        return $response ? $response->getStatusCode() : 0;
     }
}

