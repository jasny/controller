<?php

namespace Jasny\Controller\Session;

use Jasny\Controller\Session\Flash;

/**
 * @covers Jasny\Controller\Session\Flash
 */
class FlashTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete();
    }

    /**
     * Test flash
     *
     * @dataProvider flashProvider
     * @param object $data
     */
    public function testFlash($data)
    {
        $flash = new Flash();
        $this->assertFlashEmpty($flash);

        //Set flash
        $flash->set($data->type, $data->message);
        $this->assertFlashDataCorrect($flash, $data);
        $this->assertFlashIsIssued($flash, $data);

        //Get data
        $setData = $flash->get();
        $this->assertFlashDataCorrect($flash, $data);
        $this->assertFlashIsIssued($flash, $data);
        $this->assertEquals($data, $setData, "Flash data was not got correctly");

        //Clear
        $flash->clear();
        $this->assertFlashEmpty($flash);

        //Set from session
        $_SESSION['flash'] = $data;
        $this->assertFlashIsIssued($flash, $data);

        //When flash is set only in session, not by 'set' method, then getting it's data removes flash from session, so they only remain in flash object
        $this->assertFlashDataCorrect($flash, $data);
        $this->assertFalse(isset($_SESSION['flash']), "Session flash variable should be empty");
        $this->assertTrue($flash->isIssued(), "Flash should be issued");

        //Clear
        $flash->clear();
        $this->assertFlashEmpty($flash);

        //Reissue from session
        $_SESSION['flash'] = $data;
        $flash->reissue();

        $this->assertFlashDataCorrect($flash, $data);
        $this->assertFlashIsIssued($flash, $data);

        //Reissue from object data
        unset($_SESSION['flash']);
        $flash->reissue();        

        $this->assertFlashDataCorrect($flash, $data);
        $this->assertFlashIsIssued($flash, $data);

        //Clear
        $flash->clear();
        $this->assertFlashEmpty($flash);
    }

    /**
     * Provide data for testing flash
     *
     * @return array
     */
    public function flashProvider()
    {
        return [
            [(object)['type' => 'test_type', 'message' => 'Test message']],
            [(object)['type' => 'test_type', 'message' => '']]
        ];
    }

    /**
     * Test 'set' method with wrong params
     *
     * @dataProvider setNegativeProvider
     * @param object $data
     */
    public function testSetNegative($data)
    {
        $flash = new Flash();

        $this->expectException(InvalidArgumentException::class);

        $flash->set($data->type, $data->message);
    }

    /**
     * Provide data for testing wrong set
     *
     * @return array
     */
    public function setNegativeProvider()
    {
        return [
            [(object)['type' => '', 'message' => 'Test message']]
        ];
    }

    /**
     * Assert that flash is not set
     *
     * @param Flash $flash
     */
    public function assertFlashEmpty($flash)
    {
        $this->assertFalse(isset($_SESSION['flash']), "Session flash variable should be empty");
        $this->assertFalse($flash->isIssued(), "Flash should not be issued");
        $this->assertEmpty($flash->get(), "Flash should be empty");
        $this->assertEmpty($flash->getType(), "Flash type should not be set");
        $this->assertEmpty($flash->getMessage(), "Message should be empty");
        $this->assertEmpty((string)$flash, "Flash should be empty");
    }

    /**
     * Assert that flash is issued
     *
     * @param Flash $flash
     * @param object $data
     */
    public function assertFlashIsIssued($flash, $data)
    {
        $this->assertEquals($data, $_SESSION['flash'], "Flash data was not set correctly");
        $this->assertTrue($flash->isIssued(), "Flash should be issued");
    }

    /**
     * Assert that flash data is correct
     *
     * @param Flash $flash
     * @param object $data 
     */
    public function assertFlashDataCorrect($flash, $data)
    {
        $this->assertEquals($data->type, $flash->getType(), "Type was not got correctly");
        $this->assertEquals($data->message, $flash->getMessage(), "Message was not got correctly");
        $this->assertEquals($data->message, (string)$flash, "Message was not got correctly when presenting flash as string");
    }
}
