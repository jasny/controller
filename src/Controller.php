<?php
declare(strict_types=1);

namespace Jasny;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller base class
 */
abstract class Controller implements ControllerInterface
{
    use Traits\Input,
        Traits\Header,
        Traits\Output,
        Traits\CheckRequest,
        Traits\CheckResponse;
    
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    
    /**
     * Get request, set for controller
     */
    protected function getRequest(): ServerRequestInterface
    {
        if (!isset($this->request)) {
            throw new \LogicException("Request not set, the controller has not been invoked");
        }
        
        return $this->request;
    }

    /**
     * Get response, set for controller
     */
    protected function getResponse(): ResponseInterface
    {
        if (!isset($this->response)) {
            throw new \LogicException("Response not set, the controller has not been invoked");
        }
        
        return $this->response;
    }

    /**
     * Set the response
     */
    protected function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * Run the controller
     */
    abstract protected function run(): void;


    /**
     * Invoke the controller.
     */
    protected function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;

        $this->run();
        
        return $this->getResponse();
    }
}
