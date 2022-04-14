<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller methods to check the request
 */
trait CheckRequest
{
    /**
     * Get request, set for controller
     */
    abstract protected function getRequest(): ServerRequestInterface;
    
    
    /**
     * Check if request is GET request
     */
    protected function isGetRequest(): bool
    {
        $method = $this->getRequest()->getMethod();

        return !$method || $method === 'GET';
    }

    /**
     * Check if request is POST request
     */
    protected function isPostRequest(): bool
    {
        return $this->getRequest()->getMethod() === 'POST';
    }

    /**
     * Check if request is PUT request
     */
    protected function isPutRequest(): bool
    {
        return $this->getRequest()->getMethod() === 'PUT';
    }

    /**
     * Check if request is DELETE request
     */
    protected function isDeleteRequest(): bool
    {
        return $this->getRequest()->getMethod() === 'DELETE';
    }
    
    /**
     * Check if request is HEAD request
     */
    protected function isHeadRequest(): bool
    {
        return $this->getRequest()->getMethod() === 'HEAD';
    }
    

    /**
     * Returns the HTTP referer if it is on the current host
     */
    protected function getLocalReferer(): string
    {
        $request = $this->getRequest();
        $referer = $request->getHeaderLine('HTTP_REFERER');
        $host = $request->getHeaderLine('HTTP_HOST');

        return $referer && parse_url($referer, PHP_URL_HOST) === $host ? $referer : '';
    }
}
