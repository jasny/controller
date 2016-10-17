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
}

