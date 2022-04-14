<?php

namespace Jasny\Traits;

use Jasny\Traits\RouteActionController;
use Psr\Http\Message\ServerRequestInterface;
use Jasny\Traits\TestHelper;

/**
 * @covers Jasny\Controller\RouteAction
 */
class RouteActionTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper {
        getController as private _getController;
    }

    /**
     * @return string
     */
    protected function getControllerClass()
    {
        return RouteActionController::class;
    }
    
    /**
     * Get mock controller
     * 
     * @param array  $methods
     * @param string $className
     * @return RouteActionController|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getController($methods = [], $className = null)
    {
        return $this->_getController(
            array_merge($methods, ['getRequest', 'defaultAction', 'runTestAction', 'notFound', 'before', 'after']),
            $className
        );
    }



    public function actionProvider()
    {
        return [
            [(object)['args' => ['woo']], 'defaultAction', ['woo']],
            [(object)['action' => 'run-test'], 'runTestAction'],
            [(object)['action' => 'non-existent'], 'notFound']
        ];
    }
    
    /**
     * Test running controller with different actions
     * @dataProvider actionProvider
     * 
     * @param object $route
     * @param string $method
     * @param array  $args
     */
    public function testRunAction($route, $method, array $args = [])
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn($route);
        
        $this->getMockBuilder($method);
        
        $controller = $this->getController();
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        
        if ($method === 'notFound') {
            $controller->expects($this->once())->method($method)->with(...$args);
        } else {
            $controller->expects($this->once())->method('before')->id('before');
            $controller->expects($this->once())->method($method)->id('action')->after('before')->with(...$args);
            $controller->expects($this->once())->method('after')->after('action');
        }
        
        foreach (['defaultAction', 'runTestAction', 'notFound'] as $fn) {
            if ($fn !== $method) {
                $controller->expects($this->never())->method($fn);
            }
        }
        
        $controller->run();
    }
    
    
    /**
     * Provide data for testing run method
     */
    public function argumentsProvider()
    {
        return [
            [(object)['foo' => 'value1'], ['value1', null]],
            [['foo' => 'value1'], ['value1', null]],
            [(object)['foo' => 'value1', 'bar' => 'value2'], ['value1', 'value2']],
            [(object)['bar' => 'value1', 'foo' => 'value2'], ['value2', 'value1']],
            [(object)['qux' => 'value1', 'foo' => 'value2'], ['value2', null]],
            [(object)['args' => ['value1', 'value2']], ['value1', 'value2']],
            [(object)['args' => ['value1', 'value2', 'value3']], ['value1', 'value2', 'value3']],
        ];
    }

    /**
     * Test running controller with different arguments
     * @dataProvider argumentsProvider
     * 
     * @param object $route
     * @param array  $expect
     */
    public function testRunArgument($route, array $expect)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn($route);
        
        $controller = $this->getController();
        $controller->expects($this->any())->method('getRequest')->willReturn($request);

        $controller->expects($this->once())->method('defaultAction')->with(...$expect);
        $controller->expects($this->never())->method('runTestAction');
        $controller->expects($this->never())->method('notFound');
        
        $controller->run();
    }
    
    
    /**
     * @expectedException \LogicException
     */
    public function testRunWithoutRoute()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn(null);
        
        $controller = $this->getController();
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        
        $controller->run();
    }
    
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected route to be a stdClass object, not a string
     */
    public function testRunWithInvalidRoute()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn('hello');
        
        $controller = $this->getController();
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        
        $controller->run();
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Missing argument 'foo' for RunMissingArgumentController::defaultAction()
     */
    public function testRunMissingArgument()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn((object)['bar' => 20]);
        
        $controller = $this->getController([], 'RunMissingArgumentController');
        $controller->expects($this->any())->method('getRequest')->willReturn($request);
        
        $controller->run();
    }

    public function testCancel()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('route')->willReturn((object)[]);

        $controller = $this->getController();
        $controller->method('getRequest')->willReturn($request);
        
        $controller->expects($this->once())->method('before')->willReturnCallback(\Closure::bind(function() {
            $this->cancel();
        }, $controller));
        
        $controller->expects($this->never())->method('defaultAction');
        
        $controller->expects($this->once())->method('after');
        
        $controller->run();
    }
}
