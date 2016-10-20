<?php

require_once dirname(__DIR__) . '/support/TestController.php';

use Jasny\Controller\RouteAction;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers Jasny\Controller\RouteAction
 */
class RouteActionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test running controller action
     *
     * @dataProvider runPositiveProvider
     * @param object $route
     */
    public function testRunPositive($route)
    {
        $controller = new TestController();
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->method('getAttribute')->with($this->equalTo('route'))->will($this->returnValue($route));

        $result = $controller($request, $response);
        $args = !empty($route->args) ? $route->args : [$route->param1, isset($route->param2) ? $route->param2 : 'defaultValue'];

        $this->assertEquals(get_class($response), get_class($result), "Controller should return 'ResponseInterface' instance");
        $this->assertEquals($args[0], $result->param1, "First route parameter was not passed correctly");
        $this->assertEquals($args[1], $result->param2, "Second route parameter was not passed correctly");

        if (isset($route->action)) {
            $this->assertTrue($result->actionCalled, "Controller action was not called");
            $this->assertFalse(isset($result->defaultActionCalled), "Controller default action was called"); 
        } else {
            $this->assertTrue($result->defaultActionCalled, "Controller default action was not called");
            $this->assertFalse(isset($result->actionCalled), "Controller non-default action was called");
        }
    }

    /**
     * Provide data for testing run method
     */
    public function runPositiveProvider()
    {
        return [
            [(object)['controller' => 'TestController', 'param1' => 'value1']],
            [(object)['controller' => 'TestController', 'param1' => 'value1', 'param2' => 'value2']],
            [(object)['controller' => 'TestController', 'args' => ['value1', 'value2']]],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param1' => 'value1']],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param1' => 'value1', 'param2' => 'value2']],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'args' => ['value1', 'value2']]]
        ];
    }

    /**
     * Test running controller action
     *
     * @dataProvider runNegativeProvider
     * @param object $route
     * @param int $errorCode
     * @param string $errorMessage
     */
    public function testRunNegative($route, $errorCode, $errorMessage)
    {
        $controller = new TestController();
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->method('getAttribute')->with($this->equalTo('route'))->will($this->returnValue($route));

        $this->expectResponseError($response, $errorCode, $errorMessage);

        $result = $controller($request, $response);

        $this->assertEquals(get_class($response), get_class($result), "Controller should return 'ResponseInterface' instance");
    }

    /**
     * Provide data for testing run method
     */
    public function runNegativeProvider()
    {
        return [
            [(object)['controller' => 'TestController', 'action' => 'nonExistMethod'], 404, 'Not Found'],
            [(object)['controller' => 'TestController', 'action' => 'test-run'], 400, 'Bad Request'],
            [(object)['controller' => 'TestController', 'action' => 'test-run', 'param2' => 'value2'], 400, 'Bad Request']
        ];
    }

    /**
     * Expect that response will be set to error state
     *
     * @param ResponseInterface $response
     * @param int $code
     * @param string $message
     */
    public function expectResponseError($response, $code, $message)
    {   
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($this->equalTo($message));

        $response->expects($this->once())->method('getBody')->will($this->returnValue($stream));
        $response->expects($this->once())->method('withStatus')->with($this->equalTo($code))->will($this->returnSelf());
    }
}
