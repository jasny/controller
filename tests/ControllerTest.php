<?php

namespace Jasny\Test\Controller;

use Jasny\Controller\Controller;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \Jasny\Controller\Controller
 */
class ControllerTest extends TestCase
{
    /**
     * Test running controller
     */
    public function testInvoke()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $request->expects($this->exactly(3))->method('getAttribute')
            ->withConsecutive(['route:action', 'process'], ['route:{foo}'], ['route:{bar}'])
            ->willReturnOnConsecutiveCalls('process', '42', null);
        $response->expects($this->once())->method('withStatus')->willReturn($finalResponse);

        $controller = new class ($this) extends Controller {
            public $isCalled = false;
            function __construct(protected TestCase $test) { }

            function process(int $foo, string $bar = 'red') {
                $this->test->assertEquals(42, $foo);
                $this->test->assertEquals('red', $bar);

                $this->isCalled = true;
                $this->status(100);
            }
        };

        $result = $controller($request, $response);
        $this->assertEquals($finalResponse, $result);

        $this->assertTrue($controller->isCalled);
    }
}
