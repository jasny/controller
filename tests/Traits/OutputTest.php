<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use Jasny\Test\Controller\InContextOf;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Jasny\Controller\Traits\Output
 */
class OutputTest extends TestCase
{
    use InContextOf;

    /**
     * Provide data for testing output
     *
     * @return array
     */
    public function outputProvider()
    {
        return [
            ['hello world', 'text', 'text/plain'],
            ['hello world', 'text/plain', 'text/plain'],
            ['hello world', 'text/plain; charset=utf-8', 'text/plain; charset=utf-8'],
            ['abc();', 'js', 'application/javascript'],
            ['h1 { color: blue; }', 'css', 'text/css'],
            ['{ "testKey": "testValue" }', 'json', 'application/json'],
        ];
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutputWithFormat(string $content, string $format, string $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('write')->with($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withHeader')
            ->with( 'Content-Type', $contentType)
            ->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn() => $controller->output($content, $format));
    }

    /**
     * Test output, getting the format from the Content-Type response header
     */
    public function testOutputWithoutFormat()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with('hello');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->never())->method('withHeader');

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn() => $controller->output('hello'));
    }

    /**
     * Test output, getting the format from the Content-Type response header
     */
    public function testOutputWithUnknownMime()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->never())->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->never())->method('withHeader');

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Format 'something' doesn't correspond with a MIME type");

        $this->inContextOf($controller, fn() => $controller->output('hello', 'something'));
    }

    public function testJson()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('write')->with('{"foo":42,"bar":null}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withHeader')
            ->with( 'Content-Type', 'application/json')
            ->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn() => $controller->json(['foo' => 42, 'bar' => null]));
    }
}
