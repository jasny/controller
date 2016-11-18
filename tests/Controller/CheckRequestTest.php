<?php

namespace Jasny\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Jasny\Controller\TestHelper;

/**
 * @covers Jasny\Controller\CheckRequest
 */
class CheckRequestTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * Provide data for testing functions that determine request method
     *
     * @return array
     */
    public function requestMethodProvider()
    {
        return [
            ['GET'], ['POST'], ['PUT'], ['DELETE'], ['HEAD']
        ];
    }

    /**
     * Test functions that check request method
     *
     * @dataProvider requestMethodProvider
     * @param string $method
     */
    public function testRequestMethod($method)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->will($this->returnValue($method));

        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->will($this->returnValue($request));

        $this->assertEquals($method === 'GET', $controller->isGetRequest());
        $this->assertEquals($method === 'POST', $controller->isPostRequest());
        $this->assertEquals($method === 'PUT', $controller->isPutRequest());
        $this->assertEquals($method === 'DELETE', $controller->isDeleteRequest());
        $this->assertEquals($method === 'HEAD', $controller->isHeadRequest());
    }

    /**
     * Provide data fot testing 'getLocalReferer' function
     *
     * @return array
     */
    public function localRefererProvider()
    {
        return [
            ['http://google.com/path', 'example.com', null],
            ['http://example.com/', 'example.com', '/'],
            ['http://www.example.com/path', 'example.com', null],
        ];
    }

    /**
     * Test 'getLocalReferer' funtion
     *
     * @dataProvider localRefererProvider
     * @param string $referer
     * @param string $host
     * @param boolean $local
     */
    public function testLocalReferer($referer, $host, $local)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->exactly(2))->method('getHeaderLine')->withConsecutive(
            [$this->equalTo('HTTP_REFERER')],
            [$this->equalTo('HTTP_HOST')]
        )->willReturnOnConsecutiveCalls($referer, $host);

        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->will($this->returnValue($request));

        $result = $controller->getLocalReferer();

        $local ?
            $this->assertEquals($referer, $result, "Local referer should be returned") :
            $this->assertEquals('', $result, "Local referer should not be returned");
    }
}
