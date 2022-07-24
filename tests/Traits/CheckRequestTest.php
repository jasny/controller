<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use Jasny\Test\Controller\InContextOf;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Traits\CheckRequest
 */
class CheckRequestTest extends TestCase
{
    use InContextOf;

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

        $controller = new class ($this, $request) extends Controller {
            public function __construct(public CheckRequestTest $test, protected ServerRequestInterface $request) {}

            protected function getRequest(): ServerRequestInterface {
                return $this->request;
            }

            public function assertRequestMethod(string $method) {
                $this->test->assertEquals($method === 'GET', $this->isGetRequest());
                $this->test->assertEquals($method === 'POST', $this->isPostRequest());
                $this->test->assertEquals($method === 'PUT', $this->isPutRequest());
                $this->test->assertEquals($method === 'DELETE', $this->isDeleteRequest());
                $this->test->assertEquals($method === 'HEAD', $this->isHeadRequest());
            }
        };

        $controller->assertRequestMethod($method);
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
            [$this->equalTo('Referer')],
            [$this->equalTo('Host')]
        )->willReturnOnConsecutiveCalls($referer, $host);

        $controller = $this->createPartialMock(Controller::class, ['getRequest']);
        $controller->method('getRequest')->willReturn($request);

        $this->assertEquals(
            $local ? $referer : null,
            $this->inContextOf($controller, fn() => $controller->getLocalReferer())
        );
    }
}
