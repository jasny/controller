<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Cookies;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\Cookies
 */
class CookiesTest extends TestCase
{
    protected Cookies $parameter;

    public function setUp(): void
    {
        $this->parameter = new Cookies();
    }

    public function test()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getCookieParams')
            ->willReturn(['number' => 42, 'color' => 'red']);

        $this->assertEquals(
            ['number' => 42, 'color' => 'red'],
            $this->parameter->getValue($request, 'foo', null)
        );
    }
}
