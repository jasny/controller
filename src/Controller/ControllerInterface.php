<?php

namespace Jasny;

/**
 * Interface for controllers
 */
interface ControllerInterface
{
    /**
     * Run the controller as function
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
