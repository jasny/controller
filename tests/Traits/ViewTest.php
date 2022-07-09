<?php

namespace Jasny\Controller\Traits\View;

use Jasny\Controller\Traits\View;
use Jasny\Controller\ViewInterface;
use Jasny\Controller\Traits\TestHelper;
use Jasny\Controller\Traits\Session\Flash;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers Jasny\Controller\View
 */
class ViewTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * @return string
     */
    protected function getControllerClass()
    {
        return View::class;
    }
    
    public function testGetViewer()
    {
        $viewer = $this->createMock(ViewInterface::class);
        
        $controller = $this->getController();
        $controller->setViewer($viewer);
        
        $this->assertSame($viewer, $controller->getViewer());
    }
    
    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Viewer has not been set
     */
    public function testGetViewerNotSet()
    {
        $this->getController()->getViewer();
    }
    
    
    public function testGetViewPath()
    {
        $this->assertSame(getcwd(), $this->getController()->getViewPath());
    }
    
    
    public function testView()
    {
        $uri = $this->createMock(UriInterface::class);
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($uri);
        
        $response = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);
        
        $flash = $this->createMock(Flash::class);
        
        $name = 'foo';
        $context = ['color' => 'blue', 'animal' => 'monkey'];
        
        $viewer = $this->createMock(ViewInterface::class);
        $viewer->expects($this->once())->method('render')
            ->with($response, $name, $this->identicalTo($context + ['current_url' => $uri, 'flash' => $flash]))
            ->willReturn($finalResponse);
        
        $controller = $this->getController(['flash']);
        $controller->method('getRequest')->willReturn($request);
        $controller->method('getResponse')->willReturn($response);
        $controller->method('flash')->willReturn($flash);
        $controller->expects($this->once())->method('setResponse')->with($finalResponse);
        $controller->setViewer($viewer);
        
        $controller->view($name, $context);
    }
}
