<?php

require_once dirname(__DIR__) . '/support/TestController.php';

use Jasny\Controller\RouteAction;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteActionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test running controller action
     *
     * @dataProvider runProvider
     * @param [type] $[name] [<description>]
     */
    public function testRun($route, $positive)
    {
        $controller = new TestController();
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->method('getAttribute')->with($this->equalTo('route'))->will($this->returnValue($route));

        if (!$positive) $this->expectException(\RuntimeException::class);

        $result = $controller($request, $response);
        $args = !empty($route->args) ? $route->args : [$route->param1, isset($route->param2) ? $route->param2 : 'defaultValue'];

        $this->assertEquals(get_class($response), get_class($result), "Controller should return 'ResponseInterface' instance");
        $this->assertTrue($result->actionCalled, "Controller action was not called");
        $this->assertEquals($args[0], $result->param1, "First route parameter was not passed correctly");
        $this->assertEquals($args[1], $result->param2, "Second route parameter was not passed correctly");
    }

    /**
     * Provide data for testing run method
     */
    public function runProvider()
    {
        return [
            [(object)['controller' => 'TestController'], false],
            [(object)['controller' => 'TestController', 'action' => 'nonExistMethod'], false],
            [(object)['controller' => 'TestController', 'action' => 'test-run'], false],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param2' => 'value2'], false],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param1' => 'value1'], true],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param1' => 'value1', 'param2' => 'value2'], true],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'args' => ['value1', 'value2']], true]
        ];
    }

    /**
     * Test running controller without setting request and response
     */
    public function testRunNoRequest()
    {
        $controller = new TestController();

        $this->expectException(\RuntimeException::class);

        $controller->run();
    }
}
