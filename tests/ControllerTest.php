<?php

use Jasny\Controller;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test running controller
     */
    public function testInvoke()
    {
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMockForAbstractClass();
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('run')->will($this->returnValue($response));

        $result = $controller($request, $response);

        $this->assertEquals($response, $result, "Invoking controller should return 'ResponseInterface' instance");
        $this->assertEquals($response, $controller->getResponse(), "Can not get 'ResponseInterface' instance from controller");
        $this->assertEquals($request, $controller->getRequest(), "Can not get 'ServerRequestInterface' instance from controller");
    }
}
