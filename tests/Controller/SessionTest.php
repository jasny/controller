<?php

namespace Jasny\Traits;

use Jasny\Traits\Session;
use Jasny\Traits\Session\Flash;
use Psr\Http\Message\ServerRequestInterface;
use Jasny\Traits\TestHelper;

/**
 * @covers Jasny\Controller\Session
 * @internal There is some tight coupling between the Session trait and Flash class.
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * Get the controller class
     * 
     * @return string
     */
    protected function getControllerClass()
    {
        return Session::class;
    }
   
    
    public function testUseSession()
    {
        $session = new \ArrayObject();
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('session')->willReturn($session);
        
        $controller = $this->getController();
        $controller->expects($this->once())->method('getRequest')->willReturn($request);
        
        $controller->useSession();
        
        $this->assertAttributeSame($session, 'session', $controller);
    }
    
    public function testUseGlobalSession()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('session')->willReturn(null);
        
        $controller = $this->getController();
        $controller->expects($this->once())->method('getRequest')->willReturn($request);
        
        $controller->useSession();
        
        $_SESSION['foo'] = 'bar'; // Change the global session to make sure it's set by reference
        
        $this->assertAttributeEquals(['foo' => 'bar'], 'session', $controller);
    }
    
    public function testGetFlash()
    {
        $session = new \ArrayObject();
        
        $controller = $this->getController();
        $this->setPrivateProperty($controller, 'session', $session);
        
        $flash = $controller->flash();
        $this->assertInstanceOf(Flash::class, $flash);
        $this->assertEmpty($flash->get());
        
        $this->assertAttributeSame($session, 'session', $flash);
        
        $this->assertSame($flash, $controller->flash());
    }
   
    public function testSetFlash()
    {
        $flash = $this->createMock(Flash::class);
        $flash->expects($this->once())->method('set')->with('notice', 'hello world');
        
        $controller = $this->getController();
        $this->setPrivateProperty($controller, 'flash', $flash);
        
        $result = $controller->flash('notice', 'hello world');
        
        $this->assertSame($flash, $result);
    }
}
