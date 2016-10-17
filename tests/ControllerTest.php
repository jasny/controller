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

        $this->assertEquals($response, $result);
        $this->assertEquals($response, $controller->getResponse());
        $this->assertEquals($request, $controller->getRequest());
    }
}
