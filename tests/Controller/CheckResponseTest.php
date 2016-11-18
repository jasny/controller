<?php

use Psr\Http\Message\ResponseInterface;
use Jasny\Controller\TestHelper;

/**
 * @covers Jasny\Controller\CheckResponse
 */
class ControllerTest extends PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * Provide data for testing status methods
     *
     * @return array
     */
    public function responseStatusProvider()
    {
        return [
            [null, 'successful'],
            [100, 'informational'], [199, 'informational'],
            [200, 'successful'], [201, 'successful'], [299, 'successful'],
            [300, 'redirect'], [304, 'redirect'], [399, 'redirect'],
            [400, 'client error'], [403, 'client error'], [499, 'client error'],
            [500, 'server error'], [503, 'server error'],
            [999, 'unkown']
        ];
    }

    /**
     * Test functions that check response status code
     * @dataProvider responseStatusProvider
     * 
     * @param int    $code status code
     * @param string $type
     */
    public function testResponseStatus($code, $type)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->will($this->returnValue($code));

        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->assertSame($type === 'informational', $controller->isInformational(), 'isInformational');
        $this->assertSame($type === 'successful', $controller->isSuccessful(), 'isSuccessful');
        $this->assertSame($type === 'redirect', $controller->isRedirection(), 'isRedirection');
        $this->assertSame($type === 'client error', $controller->isClientError(), 'isClientError');
        $this->assertSame($type === 'server error', $controller->isServerError(), 'isServerError');
        $this->assertSame(in_array($type, ['client error', 'server error']), $controller->isError(), 'isError');
    }
}
