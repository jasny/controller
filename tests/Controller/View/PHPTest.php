<?php

namespace Jasny\Controller\Traits\View;

use Jasny\Controller\Traits\View;
use Jasny\Controller\View\PHP as PHPView;
use Jasny\Controller\Traits\TestHelper;

/**
 * @covers Jasny\Controller\View\PHP
 */
class PHPTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper {
        getController as private _getController;
    }

    /**
     * @return string
     */
    protected function getControllerClass()
    {
        return View\PHP::class;
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
        $controller = $this->_getController(array_merge($methods, ['createPHPView', 'getViewPath']), $className);
        $controller->method('getViewPath')->willReturn('/tmp');
        
        return $controller;
    }

    
    public function testGetViewer()
    {
        $viewer = $this->createMock(PHPView::class);
        
        $controller = $this->getController();
        $controller->method('createPHPView')->willReturn($viewer);
        
        $this->assertSame($viewer, $controller->getViewer());
        
        // Idempotent
        $this->assertSame($viewer, $controller->getViewer());
    }
}
