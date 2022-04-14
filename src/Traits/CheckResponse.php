<?php
declare(strict_types=1);

namespace Jasny\Traits;

use Psr\Http\Message\ResponseInterface;

/**
 * Methods to check the response
 */
trait CheckResponse
{
    /**
     * Get response, set for controller
     */
    abstract protected function getResponse(): ResponseInterface;

    
    /**
     * Check if response is a 1xx informational
     */
    protected function isInformational(): bool
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;
        return $code >= 100 && $code < 200;
    }
    
    /**
     * Check if response is 2xx succesful, or empty
     */
    protected function isSuccessful(): bool
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;
        return $code >= 200 && $code < 300;
    }
    
    /**
     * Check if response is a 3xx redirect
     */
    protected function isRedirection(): bool
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;
        return $code >= 300 && $code < 400;
    }
    
    /**
     * Check if response is a 4xx client error
     */
    protected function isClientError(): bool
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;
        return $code >= 400 && $code < 500;
    }
    
    /**
     * Check if response is a 5xx redirect
     */
    protected function isServerError(): bool
    {
        $code = $this->getResponse()->getStatusCode() ?: 200;
        return $code >= 500 && $code < 600;
    }

    /**
     * Check if response is 4xx or 5xx error
     */
    protected function isError(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }
}
