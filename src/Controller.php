<?php

namespace Jasny;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller
 */
abstract class Controller
{
    use Controller\Input,
        Controller\Output,
        Controller\CheckRequest,
        Controller\CheckResponse;
    
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
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        if (!isset($this->request)) {
            throw new \LogicException("Request not set, the controller has not been invoked");
        }
        
        return $this->request;
    }

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        if (!isset($this->response)) {
            throw new \LogicException("Response not set, the controller has not been invoked");
        }
        
        return $this->response;
    }

    /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }
    
    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    abstract protected function run();


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

        if (method_exists($this, 'useSession')) {
            $this->useSession();
        }
        
        return $this->run();
    }
}
