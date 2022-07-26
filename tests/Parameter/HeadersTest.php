<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Headers;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HeadersTest extends TestCase
{
    public function test()
    {
        $parameter = new Headers();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaders')->willReturn([
            'Content-Type' => ['application/x-www-form-urlencoded'],
            'Accept' => ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9', 'image/webp', '*/*;q=0.8'],
        ]);

        $expected = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8',
        ];

        $value = $parameter->getValue($request, 'foo', 'array');

        $this->assertEquals($expected, $value);
    }
}
