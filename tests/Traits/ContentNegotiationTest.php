<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use Jasny\Test\Controller\InContextOf;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Traits\ContentNegotiation
 */
class ContentNegotiationTest extends TestCase
{
    use InContextOf;

    public function contentTypeProvider()
    {
        return [
            [
                'text/html',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9', '*/*;q=0.8'],
                'text/html, application/xhtml+xml, application/xml'
            ],
            [
                'text/html',
                ['application/xhtml+xml', 'application/xml;q=0.9', 'text/html;q=0.99', '*/*;q=0.8'],
                'text/html, application/xml'
            ],
            [
                '',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9'],
                'text/plain, application/json'
            ],
        ];
    }

    /**
     * @dataProvider contentTypeProvider
     */
    public function testNegotiateContentType(string $expected, array $priorities, string $accept)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')->with('Accept')
            ->willReturn($accept);

        $controller = $this->createPartialMock(Controller::class, ['getRequest', 'header']);
        $controller->method('getRequest')->willReturn($request);
        $controller->expects($expected !== '' ? $this->once() : $this->never())
            ->method('header')
            ->with('Content-Type', $expected);

        $result = $this->inContextOf($controller, fn() => $controller->negotiateContentType($priorities));
        $this->assertEquals($expected, $result);
    }

    public function languageProvider()
    {
        return [
            [
                'en',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                'en, fr'
            ],
            [
                '',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                'ru, es'
            ],
        ];
    }

    /**
     * @dataProvider languageProvider
     */
    public function testNegotiateLanguage(string $expected, array $priorities, string $accept)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')->with('Accept-Language')
            ->willReturn($accept);

        $controller = $this->createPartialMock(Controller::class, ['getRequest', 'header']);
        $controller->method('getRequest')->willReturn($request);
        $controller->expects($expected !== '' ? $this->once() : $this->never())
            ->method('header')
            ->with('Content-Language', $expected);

        $result = $this->inContextOf($controller, fn() => $controller->negotiateLanguage($priorities));
        $this->assertEquals($expected, $result);
    }

    public function encodingProvider()
    {
        return [
            [
                'gzip',
                ['gzip', 'compress', 'deflate'],
                'gzip, compress'
            ],
            [
                '',
                ['gzip', 'compress', 'deflate'],
                'br, identity'
            ],
        ];
    }

    /**
     * @dataProvider encodingProvider
     */
    public function testNegotiateEncoding(string $expected, array $priorities, string $accept)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')->with('Accept-Encoding')
            ->willReturn($accept);

        $controller = $this->createPartialMock(Controller::class, ['getRequest', 'header']);
        $controller->method('getRequest')->willReturn($request);
        $controller->expects($expected !== '' ? $this->once() : $this->never())
            ->method('header')
            ->with('Content-Encoding', $expected);

        $result = $this->inContextOf($controller, fn() => $controller->negotiateEncoding($priorities));
        $this->assertEquals($expected, $result);
    }

    public function charsetProvider()
    {
        return [
            [
                'utf-8',
                ['utf-8', 'iso-8859-1;q=0.5'],
                'utf-8, iso-8859-1'
            ],
            [
                '',
                ['utf-8', 'iso-8859-1;q=0.5'],
                'windows-1251'
            ]
        ];
    }

    /**
     * @dataProvider charsetProvider
     */
    public function testNegotiateCharset(string $expected, array $priorities, string $accept)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')->with('Accept-Charset')
            ->willReturn($accept);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')
            ->willReturn('');

        $controller = $this->createPartialMock(Controller::class, ['getRequest', 'getResponse', 'header']);
        $controller->method('getRequest')->willReturn($request);
        $controller->method('getResponse')->willReturn($response);
        $controller->expects($this->never())->method('header');

        $result = $this->inContextOf($controller, fn() => $controller->negotiateCharset($priorities));
        $this->assertEquals($expected, $result);
    }

    public function charsetContentTypeProvider()
    {
        return [
            ['text/html'],
            ['text/html; charset=windows-1251']
        ];
    }
    /**
     * @dataProvider charsetContentTypeProvider
     */
    public function testNegotiateCharsetWithHeader(string $contentType)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')->with('Accept-Charset')
            ->willReturn('utf-8');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')
            ->willReturn($contentType);

        $controller = $this->createPartialMock(Controller::class, ['getRequest', 'getResponse', 'header']);
        $controller->method('getRequest')->willReturn($request);
        $controller->method('getResponse')->willReturn($response);
        $controller->expects($this->once())->method('header')
            ->with('Content-Type', 'text/html; charset=utf-8');

        $result = $this->inContextOf($controller, fn() => $controller->negotiateCharset(['utf-8', 'iso-8859-1;q=0.5']));
        $this->assertEquals('utf-8', $result);
    }
}
