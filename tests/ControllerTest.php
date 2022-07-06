<?php

namespace Jasny\Test\Controller;

use Jasny\Controller\Controller;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Jasny\Controller\Controller
 */
class ControllerTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject  */
    public ServerRequestInterface $request;

    /** @var ResponseInterface&MockObject  */
    public ResponseInterface $initialResponse;

    /** @var ResponseInterface&MockObject  */
    public ResponseInterface $finalResponse;

    protected Controller $controller;

    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->initialResponse = $this->createMock(ResponseInterface::class);
        $this->finalResponse = $this->createMock(ResponseInterface::class);

        $this->controller = new class ($this) extends Controller {
            public $called;
            function __construct(protected ControllerTest $test) { }

            function process(int $foo, string $bar = 'red') {
                $this->called = __FUNCTION__;

                $this->test->assertEquals(42, $foo);
                $this->test->assertEquals('red', $bar);

                $this->test->assertSame($this->test->request, $this->getRequest());
                $this->test->assertSame($this->test->initialResponse, $this->getResponse());

                $this->status(100);
            }

            function foo() {
                $this->called = __FUNCTION__;
                $this->status(204);
            }
        };
    }

    public function testProcess()
    {
        $this->request->expects($this->exactly(3))->method('getAttribute')
            ->withConsecutive(['route:action', 'process'], ['route:{foo}'], ['route:{bar}'])
            ->willReturnOnConsecutiveCalls('process', '42', null);
        $this->initialResponse->expects($this->once())->method('withStatus')
            ->with(100)
            ->willReturn($this->finalResponse);

        $result = ($this->controller)($this->request, $this->initialResponse);
        $this->assertEquals('process', $this->controller->called);
        $this->assertEquals($this->finalResponse, $result);
    }

    public function testOtherMethod()
    {
        $this->request->expects($this->once())->method('getAttribute')
            ->with('route:action')
            ->willReturn('foo');
        $this->initialResponse->expects($this->once())->method('withStatus')
            ->with(204)
            ->willReturn($this->finalResponse);

        $result = ($this->controller)($this->request, $this->initialResponse);
        $this->assertEquals('foo', $this->controller->called);
        $this->assertEquals($this->finalResponse, $result);
    }

    public function testMethodNotFound()
    {
        $this->request->expects($this->once())->method('getAttribute')
            ->with('route:action')
            ->willReturn('bar');
        $this->initialResponse->expects($this->once())->method('withStatus')
            ->with(404)
            ->willReturn($this->finalResponse);

        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('write')->with('Not found');
        $this->finalResponse->expects($this->once())->method('getBody')->willReturn($body);

        $result = ($this->controller)($this->request, $this->initialResponse);
        $this->assertNull($this->controller->called);
        $this->assertEquals($this->finalResponse, $result);
    }

    public function testBefore()
    {
        $controller = new class ($this) extends Controller {
            function __construct(protected ControllerTest $test) { }

            function before() {
                return $this->paymentRequired();
            }

            function process() {
                $this->test->fail("Process should not be called");
            }
        };

        $this->request->expects($this->once())->method('getAttribute')
            ->with('route:action')
            ->willReturn('process');
        $this->initialResponse->expects($this->once())->method('withStatus')
            ->with(402)
            ->willReturn($this->finalResponse);

        $result = $controller($this->request, $this->initialResponse);
        $this->assertEquals($this->finalResponse, $result);

    }
}
