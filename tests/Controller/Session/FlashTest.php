<?php

namespace Jasny\Controller\Session;

use Jasny\Controller\Session\Flash;

/**
 * @covers Jasny\Controller\Session\Flash
 */
class FlashTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEmpty()
    {
        $session = [];
        
        $flash = new Flash($session);
        
        $this->assertFalse($flash->isIssued(), "Flash should not be issued");
        $this->assertEmpty($flash->get(), "Flash should be empty");
        $this->assertEmpty($flash->getType(), "Flash type should not be set");
        $this->assertEquals('', $flash->getMessage(), "Message should be empty");
        $this->assertEquals('', (string)$flash, "Flash should be empty");
        
        $this->assertArrayNotHasKey('flash', $session); // Calling get will clear the session data
    }
    
    public function testGetWithExistingFlash()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);
        
        $this->assertTrue($flash->isIssued());
        $this->assertEquals((object)['type' => 'notice', 'message' => 'Foo bar zoo'], $flash->get());
        $this->assertEquals('notice', $flash->getType());
        $this->assertEquals('Foo bar zoo', $flash->getMessage());
        $this->assertEquals('Foo bar zoo', (string)$flash);
        
        $this->assertArrayNotHasKey('flash', $session);
    }

    public function testExistingFlashWithoutGet()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);
        
        $this->assertTrue($flash->isIssued());
        
        $this->assertArrayHasKey('flash', $session);
        $this->assertEquals(['type' => 'notice', 'message' => 'Foo bar zoo'], $session['flash']);
    }
    
    public function testSetFlash()
    {
        $session = [];
        $flash = new Flash($session);
        
        $flash->set('info', 'just smile');
        
        $this->assertEquals(['flash' => ['type' => 'info', 'message' => 'just smile']], $session);
        
        $this->assertEquals((object)['type' => 'info', 'message' => 'just smile'], $flash->get());
    }
    
    public function testClear()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);
        
        $flash->clear();
        
        $this->assertEmpty($flash->get());
        $this->assertEmpty($session);
    }
    
    public function testClearAfterGet()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);

        $this->assertEquals((object)['type' => 'notice', 'message' => 'Foo bar zoo'], $flash->get());
        
        $flash->clear();
        
        $this->assertEmpty($flash->get());
        $this->assertEmpty($session);
    }
    
    public function testClearAfterSet()
    {
        $session = [];
        $flash = new Flash($session);
        
        $flash->set('info', 'just smile');
        
        $this->assertEquals(['flash' => ['type' => 'info', 'message' => 'just smile']], $session);
        
        $flash->clear();
        
        $this->assertFalse($flash->isIssued());
        $this->assertEmpty($session);
    }
    
    public function testReissueAfterGet()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);
        
        $this->assertEquals((object)['type' => 'notice', 'message' => 'Foo bar zoo'], $flash->get());
        $this->assertEmpty($session);
        
        $flash->reissue();
        
        $this->assertArrayHasKey('flash', $session);
        $this->assertEquals(['flash' => ['type' => 'notice', 'message' => 'Foo bar zoo']], $session);
    }
    
    public function testReissueBeforeGet()
    {
        $session = [
            'flash' => [
                'type' => 'notice',
                'message' => 'Foo bar zoo'
            ]
        ];
        
        $flash = new Flash($session);
        
        $flash->reissue();
        
        $this->assertEquals((object)['type' => 'notice', 'message' => 'Foo bar zoo'], $flash->get());
        
        $this->assertArrayHasKey('flash', $session);
        $this->assertEquals(['flash' => ['type' => 'notice', 'message' => 'Foo bar zoo']], $session);
    }
}
