<?php

namespace Jasny\Controller\Traits;

use Jasny\Controller\Traits\ContentNegotiation;
use Psr\Http\Message\ServerRequestInterface;
use Jasny\Controller\Traits\TestHelper;
use Negotiation;
use Negotiation\BaseAccept;

/**
 * @covers Jasny\Controller\ContentNegotiation
 */
class ContentNegotiationTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

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
                Negotiation\Negotiator::class,
                '',
                'text/html',
                'Accept',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9', '*/*;q=0.8'],
                ['text/html', 'application/xhtml+xml', 'application/xml']
            ],
            [
                'negotiateContentType',
                Negotiation\Negotiator::class,
                '',
                '',
                'Accept',
                ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9'],
                ['text/plain', 'application/json']
            ],
            [
                'negotiateLanguage',
                Negotiation\LanguageNegotiator::class,
                'language',
                'en',
                'Accept-Language',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                ['en', 'fr']
            ],
            [
                'negotiateLanguage',
                Negotiation\LanguageNegotiator::class,
                'language',
                '',
                'Accept-Language',
                ['en', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2'],
                ['ru', 'es']
            ],
            [
                'negotiateEncoding',
                Negotiation\EncodingNegotiator::class,
                'encoding',
                'gzip',
                'Accept-Encoding',
                ['gzip', 'compress', 'deflate'],
                ['gzip', 'compress']
            ],
            [
                'negotiateEncoding',
                Negotiation\EncodingNegotiator::class,
                'encoding',
                '',
                'Accept-Encoding',
                ['gzip', 'compress', 'deflate'],
                ['br', 'identity']
            ],
            [
                'negotiateCharset',
                Negotiation\CharsetNegotiator::class,
                'charset',
                'utf-8',
                'Accept-Charset',
                ['utf-8', 'iso-8859-1;q=0.5'],
                ['utf-8', 'iso-8859-1;q=0.5']
            ],
            [
                'negotiateCharset',
                Negotiation\CharsetNegotiator::class,
                'charset',
                '',
                'Accept-Charset',
                ['utf-8', 'iso-8859-1;q=0.5'],
                ['windows-1251']
            ]
        ];
    }

    /**
     * Test negotiation
     * @dataProvider negotiateProvider
     * 
     * @param string $method
     * @param string $negotiatorClass
     * @param string $type
     * @param string $expected
     * @param string $headerName
     * @param array  $headerValue
     * @param array  $priorities
     */
    public function testNegotiate(
        $method,
        $negotiatorClass,
        $type,
        $expected,
        $headerName,
        array $headerValue,
        array $priorities
    ) {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeader')->with($this->equalTo($headerName))
            ->will($this->returnValue($headerValue));

        $expectedObj = $this->createMock(BaseAccept::class);
        $expectedObj->expects($this->once())->method('getType')->will($this->returnValue($expected));

        $negotiator = $this->createMock($negotiatorClass);
        $negotiator->expects($this->once())->method('getBest')
            ->with($this->equalTo(join(', ', $headerValue)), $this->equalTo($priorities))
            ->will($this->returnValue($expectedObj));

        $trait = $this->getController(['getRequest', 'getNegotiator']);
        $trait->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $trait->expects($this->once())->method('getNegotiator')->with($this->equalTo($type))
            ->will($this->returnValue($negotiator));

        $buildClass = $this->callPrivateMethod($trait, 'getNegotiatorName', [$type]);
        $result = $trait->{$method}($priorities);

        $this->assertEquals($buildClass, $negotiatorClass, "Obtained wrong negotiator class");
        $this->assertEquals($result, $expected, "Obtained result does not match expected result");
    }

    /**
     * Test negotiation
     * @dataProvider negotiateProvider
     * 
     * @param string $method
     * @param string $negotiatorClass
     * @param string $type
     */
    public function testGetNegotiator($method, $negotiatorClass, $type)
    {
        $controller = $this->getController();
        
        $this->assertInstanceOf($negotiatorClass, $this->callPrivateMethod($controller, 'getNegotiator', [$type]));
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
