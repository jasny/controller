<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Body;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @covers \Jasny\Controller\Parameter\Body
 */
class BodyTest extends TestCase
{
    protected Body $parameter;

    public function setUp(): void
    {
        $this->parameter = new Body();
    }

    public function testBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('hello');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getBody')->willReturn($stream);
        $request->expects($this->never())->method('getParsedBody');

        $value = $this->parameter->getValue($request, 'foo', 'string');

        $this->assertEquals('hello', $value);
    }

    public function testParsedBody()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->never())->method('getBody');
        $request->expects($this->once())->method('getParsedBody')
            ->willReturn(['number' => 42, 'color' => 'red']);

        $value = $this->parameter->getValue($request, 'foo', 'array');

        $this->assertEquals(['number' => 42, 'color' => 'red'], $value);
    }
}
