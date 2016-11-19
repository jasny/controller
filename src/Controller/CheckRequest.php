<?php

namespace Jasny\Controller;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller methods to check the request
 */
trait CheckRequest
{
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    abstract public function getRequest();
    
    
    /**
     * Check if request is GET request
     *
     * @return boolean
     */
    public function isGetRequest()
    {
        $method = $this->getRequest()->getMethod();

        return !$method || $method === 'GET';
    }

    /**
     * Check if request is POST request
     *
     * @return boolean
     */
    public function isPostRequest()
    {
        return $this->getRequest()->getMethod() === 'POST';
    }

    /**
     * Check if request is PUT request
     *
     * @return boolean
     */
    public function isPutRequest()
    {
        return $this->getRequest()->getMethod() === 'PUT';
    }

    /**
     * Check if request is DELETE request
     *
     * @return boolean
     */
    public function isDeleteRequest()
    {
        return $this->getRequest()->getMethod() === 'DELETE';
    }
    
    /**
     * Check if request is HEAD request
     *
     * @return boolean
     */
    public function isHeadRequest()
    {
        return $this->getRequest()->getMethod() === 'HEAD';
    }
    

    /**
     * Returns the HTTP referer if it is on the current host
     *
     * @return string
     */
    public function getLocalReferer()
    {
        $request = $this->getRequest();
        $referer = $request->getHeaderLine('HTTP_REFERER');
        $host = $request->getHeaderLine('HTTP_HOST');

        return $referer && parse_url($referer, PHP_URL_HOST) === $host ? $referer : '';
    }
}