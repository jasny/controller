<?php

namespace Jasny\Controller;

use Jasny\Controller\ContentNegotiation;
use Psr\Http\Message\ServerRequestInterface;
use Jasny\Controller\TestHelper;
use Negotiation\Negotiator;
use Negotiation\BaseAccept;

/**
 * @covers Jasny\Controller\ContentNegotiation
 */
class ContentNegotiationTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * Test negotiation
     *
     * @dataProvider negotiateProvider
     * @param string $result
     * @param array $header
     * @param array $priorities
     */
    public function testNegotiate($method, $negotiatorClass, $type, $expected, $headerName, array $headerValue, array $priorities)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeader')->with($this->equalTo($headerName))->will($this->returnValue($headerValue));

        $expectedObj = $this->createMock(BaseAccept::class);
        $expectedObj->expects($this->once())->method('getType')->will($this->returnValue($expected));

        $negotiator = $this->createMock($negotiatorClass);
        $negotiator->expects($this->once())->method('getBest')->with($this->equalTo(join(', ', $headerValue)), $this->equalTo($priorities))->will($this->returnValue($expectedObj));

        $trait = $this->getController(['getRequest', 'getNegotiator']);
        $trait->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $trait->expects($this->once())->method('getNegotiator')->with($this->equalTo($type))->will($this->returnValue($negotiator));

        $buildClass = $this->callPrivateMethod($trait, 'getNegotiatorName', [$type]);
        $result = $trait->{$method}($priorities);

        $this->assertEquals($buildClass, $negotiatorClass, "Obtained wrong negotiator class");
        $this->assertEquals($result, $expected, "Obtained result does not match expected result");
    }

    /**
     * Provide data for testing negotiation
     *
     * @return array
     */
    public function negotiateProvider()
    {
        return [
            [
                'negotiateContentType',
                'Negotiation\\Negotiator',
                '',
                'text/html',
                'Accept',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9', '*/*;q=0.8'],
                ['text/html', 'application/xhtml+xml', 'application/xml']
            ],
            [
                'negotiateContentType',
                'Negotiation\\Negotiator',
                '',
                '',
                'Accept',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9'],
                ['text/plain', 'application/json']
            ],
            [
                'negotiateLanguage',
                'Negotiation\\LanguageNegotiator',
                'language',
                'en',
                'Accept-Language',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                ['en', 'fr']
            ],
            [
                'negotiateLanguage',
                'Negotiation\\LanguageNegotiator',
                'language',
                '',
                'Accept-Language',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                ['ru', 'es']
            ],
            [
                'negotiateEncoding',
                'Negotiation\\EncodingNegotiator',
                'encoding',
                'gzip',
                'Accept-Encoding',
                ['gzip', 'compress', 'deflate'],
                ['gzip', 'compress']
            ],
            [
                'negotiateEncoding',
                'Negotiation\\EncodingNegotiator',
                'encoding',
                '',
                'Accept-Encoding',
                ['gzip', 'compress', 'deflate'],
                ['br', 'identity']
            ],
            [
                'negotiateCharset',
                'Negotiation\\CharsetNegotiator',
                'charset',
                'utf-8',
                'Accept-Charset',
                ['utf-8', 'iso-8859-1;q=0.5'],
                ['utf-8', 'iso-8859-1;q=0.5']
            ],
            [
                'negotiateCharset',
                'Negotiation\\CharsetNegotiator',
                'charset',
                '',
                'Accept-Charset',
                ['utf-8', 'iso-8859-1;q=0.5'],
                ['windows-1251']
            ]
        ];
    }

    /**
     * Get the controller class
     *
     * @return string
     */
    protected function getControllerClass()
    {
        return ContentNegotiation::class;
    }
}
