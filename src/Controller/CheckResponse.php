<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * Methods to check the response
 */
trait CheckResponse
{
    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    abstract protected function getResponse();

    
    /**
     * Check if response is 2xx succesful, or empty
     * 
     * @return boolean
     */
    public function isSuccessful()
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;

        return !$code || ($code >= 200 && $code < 300);
    }
    
    /**
     * Check if response is a 3xx redirect
     * 
     * @return boolean
     */
    public function isRedirection()
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;

        return $code >= 300 && $code < 400;
    }
    
    /**
     * Check if response is a 4xx client error
     * 
     * @return boolean
     */
    public function isClientError()
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;

        return $code >= 400 && $code < 500;
    }
    
    /**
     * Check if response is a 5xx redirect
     * 
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getResponse()->getStatusCode() ?: 200 >= 500;
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
}
