<?php

namespace Jasny\Controller\Traits\View;

use Jasny\Controller\Traits\View;
use Jasny\Controller\View\Twig as TwigView;
use Jasny\Controller\Traits\TestHelper;

/**
 * @covers Jasny\Controller\View\Twig
 */
class TwigTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper {
        getController as private _getController;
    }

    /**
     * @return string
     */
    protected function getControllerClass()
    {
        return View\Twig::class;
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
        $controller = $this->_getController(array_merge($methods, ['createTwigView', 'getViewPath']), $className);
        $controller->method('getViewPath')->willReturn('/tmp');
        
        return $controller;
    }

    
    public function testGetViewer()
    {
        $viewer = $this->createMock(TwigView::class);
        $viewer->expects($this->once())->method('addDefaultExtensions');
        
        $controller = $this->getController();
        $controller->method('createTwigView')->willReturn($viewer);
        
        $this->assertSame($viewer, $controller->getViewer());
        
        // Idempotent
        $this->assertSame($viewer, $controller->getViewer());
    }
}
