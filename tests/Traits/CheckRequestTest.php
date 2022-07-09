<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Traits\CheckRequest
 */
class CheckRequestTest extends TestCase
{
    /**
     * Provide data for testing functions that determine request method
     */
    public function requestMethodProvider(): array
    {
        return [
            ['GET'], ['POST'], ['PUT'], ['DELETE'], ['HEAD']
        ];
    }

    /**
     * Test functions that check request method
     *
     * @dataProvider requestMethodProvider
     */
    public function testRequestMethod(string $method)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);

        $controller = $this->createPartialMock(Controller::class, ['getRequest']);
        $controller->method('getRequest')->willReturn($request);

        $this->assertEquals($method === 'GET', $controller->isGetRequest());
        $this->assertEquals($method === 'POST', $controller->isPostRequest());
        $this->assertEquals($method === 'PUT', $controller->isPutRequest());
        $this->assertEquals($method === 'DELETE', $controller->isDeleteRequest());
        $this->assertEquals($method === 'HEAD', $controller->isHeadRequest());
    }

    /**
     * Provide data fot testing 'getLocalReferer' function.
     */
    public function localRefererProvider(): array
    {
        return [
            ['http://google.com/path', 'example.com', false],
            ['http://example.com/', 'example.com', true],
            ['http://www.example.com/path', 'example.com', false],
        ];
    }

    /**
     * Test 'getLocalReferer' funtion
     *
     * @dataProvider localRefererProvider
     */
    public function testLocalReferer(string $referer, string $host, bool $local): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->exactly(2))->method('getHeaderLine')->withConsecutive(
            [$this->equalTo('HTTP_REFERER')],
            [$this->equalTo('HTTP_HOST')]
        )->willReturnOnConsecutiveCalls($referer, $host);


        $controller = $this->createPartialMock(Controller::class, ['getRequest']);
        $controller->method('getRequest')->willReturn($request);

        $result = $controller->getLocalReferer();

        $local ?
            $this->assertEquals($referer, $result, "Local referer should be returned") :
            $this->assertEquals('', $result, "Local referer should not be returned");
    }
}
