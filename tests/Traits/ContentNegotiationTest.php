<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Traits\ContentNegotiation
 */
class ContentNegotiationTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject */
    protected ServerRequestInterface $request;

    /** @var Controller&MockObject */
    protected Controller $controller;

    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);

        $this->controller = new class($this, $this->request) extends Controller {
            public function __construct(public TestCase $test, protected ServerRequestInterface $request) {}

            protected function getRequest(): ServerRequestInterface {
                return $this->request;
            }

            public function negotiateContentType(array $priorities): string {
                return parent::negotiateContentType($priorities);
            }

            public function negotiateCharset(array $priorities): string {
                return parent::negotiateCharset($priorities);
            }

            public function negotiateEncoding(array $priorities): string {
                return parent::negotiateEncoding($priorities);
            }

            public function negotiateLanguage(array $priorities): string {
                return parent::negotiateLanguage($priorities);
            }
        };
    }

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
        $this->request->expects($this->once())->method('getHeaderLine')->with('Accept')
            ->willReturn($accept);

        $result = $this->controller->negotiateContentType($priorities);
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
        $this->request->expects($this->once())->method('getHeaderLine')->with('Accept-Language')
            ->willReturn($accept);

        $result = $this->controller->negotiateLanguage($priorities);
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
        $this->request->expects($this->once())->method('getHeaderLine')->with('Accept-Encoding')
            ->willReturn($accept);

        $result = $this->controller->negotiateEncoding($priorities);
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
        $this->request->expects($this->once())->method('getHeaderLine')->with('Accept-Charset')
            ->willReturn($accept);

        $result = $this->controller->negotiateCharset($priorities);
        $this->assertEquals($expected, $result);
    }
}
