<?php

namespace Jasny\Controller;

use Jasny\Controller\Session\Flash;
use Jasny\Controller\SessionController;
use Jasny\Controller\TestHelper;

/**
 * @covers Jasny\Controller
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function setUp()
    {
        $this->markTestIncomplete();
    }

    /**
     * Get the controller class
     * 
     * @return string
     */
    protected function getControllerClass()
    {
        return SessionController::class;
    }
   
    /**
     * Test setting flash
     *
     * @return array
     */
    public function flashProvider()
    {
        return [
            [(object)['type' => 'test_type', 'message' => 'Test message']]
        ];  
    }
    
    /**
     * Test setting flash
     *
     * @dataProvider flashProvider
     * @param object $data 
     */
    public function testFlash($data)
    {
        $controller = $this->getController();

        $flash = $controller->flash();
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEmpty($flash->get(), "Flash data should be empty");        

        $flash = $controller->flash($data->type, $data->message);
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEquals($data, $flash->get(), "Flash data is incorrect");

        $flash = $controller->flash();
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEquals($data, $flash->get(), "Flash data is incorrect");

        $flash->clear();
    }
}
