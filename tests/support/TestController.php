<?php

use Jasny\Controller;
use Jasny\Controller\RouteAction;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class for testing 'RouteAction' trait
 */
class TestController extends Controller
{
    use RouteAction;

    /**
     * Test action for executing router
     *
     * @param mixed $param1
     * @param mixed $param2
     * @return ResponseInterface
     */
    public function testAction($param1, $param2 = 'defaultValue')
    {
        $response = $this->getResponse();

        $response->actionCalled = true;
        $response->param1 = $param1;
        $response->param2 = $param2;

        return $response;
    }
}
