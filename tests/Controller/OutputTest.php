<?php

namespace Jasny\Traits;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Jasny\Traits\TestHelper;

/**
 * @covers Jasny\Controller\Output
 */
class OutputTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    
    public function getSetResponseHeaderProvider()
    {
        return [
            [true, 'withHeader'],
            [false, 'withAddedHeader']
        ];
    }
    
    /**
     * @dataProvider getSetResponseHeaderProvider
     * 
     * @param boolean $replace
     * @param string  $method
     */
    public function testSetResponseHeader($replace, $method)
    {
        $response = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);
        
        $response->expects($this->once())->method($method)->with('Foo', 'bar')->willReturn($finalResponse);
        
        $controller = $this->getController(['getResponse', 'setResponse']);
        
        $controller->expects($this->once())->method('getResponse')->willReturn($response);
        $controller->expects($this->once())->method('setResponse')->with($finalResponse);
        
        $controller->header('Foo', 'bar', $replace);
    }
    
    /**
     * Test function respondWith
     *
     * @return array
     */
    public function respondWithProvider()
    {
        return [
            [[200, 'application/json'], 200, 'application/json'],
            [[200, 'json'], 200, 'application/json'],
            [[204], 204, null],
            [[204, null], 204, null],
            [['400 Foo Bar'], [400, 'Foo Bar'], null],
            [[null, 'application/json'], null, 'application/json'],
            [['application/json'], null, 'application/json'],
            [['json'], null, 'application/json'],
            [['html'], null, 'text/html'],
            [['text'], null, 'text/plain']
        ];
    }

    /**
     * Test respondWith function
     * @dataProvider respondWithProvider
     *
     * @param array     $args
     * @param int|array $status       Expected status code or [code, phrase]
     * @param string    $contentType
     */
    public function testRespondWith($args, $status, $contentType)
    {
        $response = $this->createMock(ResponseInterface::class);
        $statusResponse = isset($status) ? $this->createMock(ResponseInterface::class) : $response;
        $finalResponse = isset($contentType) ? $this->createMock(ResponseInterface::class) : $statusResponse;

        $response->expects(isset($status) ? $this->once() : $this->never())->method('withStatus')
            ->with(...(array)$status)
            ->willReturn($statusResponse);

        $statusResponse->expects(isset($contentType) ? $this->once() : $this->never())->method('withHeader')
            ->with('Content-Type', $contentType)
            ->willReturn($finalResponse);

        $controller = $this->getController(['getResponse', 'setResponse']);

        $controller->expects($this->once())->method('getResponse')->willReturn($response);
        $controller->expects($this->once())->method('setResponse')->with($finalResponse);

        $controller->respondWith(...$args);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Format 'foo-bar-zoo' doesn't correspond with a MIME type
     */
    public function testRespondWithFormat()
    {
        $response = $this->createMock(ResponseInterface::class);

        $controller = $this->getController(['getResponse', 'setResponse']);
        $controller->expects($this->once())->method('getResponse')->willReturn($response);
        $controller->expects($this->never())->method('setResponse');

        $controller->respondWith(null, 'foo-bar-zoo');
    }


    public function implicitStatusCodeProvider()
    {
        return [
            ['ok', 200],
            ['created', 201],
            ['accepted', 202],
            ['noContent', 204],
            ['partialContent', 206, [1, 2, 100]],

            ['redirect', 303, ['example.com']],
            ['back', 303],
            ['notModified', 304],

            ['badRequest', 400, ['']],
            ['requireAuth', 401],
            ['requireLogin', 401],
            ['paymentRequired', 402],
            ['forbidden', 403],
            ['notFound', 404],
            ['conflict', 409, ['']],
            ['tooManyRequests', 429],

            ['error', 500]
        ];
    }

    /**
     * Test the default status code of different response methods
     * @dataProvider implicitStatusCodeProvider
     *
     * @param string $function
     * @param int    $code      Expected status code
     * @param array  $args
     */
    public function testImplicitStatus($function, $code, $args = [])
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->any())->method('withHeader')->willReturnSelf();

        $controller = $this->getController(['getResponse', 'output', 'getLocalReferer']);
        $controller->method('getResponse')->willReturn($response);

        $controller->$function(...$args);
    }


    public function explicitStatusCodeProvider()
    {
        return [
            ['noContent', 205],
            ['redirect', 301, ['example.com']],
            ['redirect', 307, ['example.com']],
            ['badRequest', 412, ['']],
            ['notFound', 405, ['']],
            ['error', 500, ['']]
        ];
    }

    /**
     * Test setting the status code of different response methods
     * @dataProvider explicitStatusCodeProvider
     *
     * @param string $function
     * @param int    $code      Expected status code
     * @param array  $args
     */
    public function testExlicitStatus($function, $code, $args = [])
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->any())->method('withHeader')->willReturnSelf();

        $controller = $this->getController(['getResponse', 'output', 'getLocalReferer']);
        $controller->method('getResponse')->willReturn($response);

        $args[] = $code;

        $controller->$function(...$args);
    }


    public function implicitMessageProvider()
    {
        return [
            ['paymentRequired', 'Payment required'],
            ['forbidden', 'Access denied'],
            ['notFound', 'Not found'],
            ['tooManyRequests', 'Too many requests'],

            ['error', 'An unexpected error occured']
        ];
    }

    /**
     * Test the default messages of different response methods
     * @dataProvider implicitMessageProvider
     *
     * @param string $function
     * @param string $message   Expected message
     * @param array  $args
     */
    public function testImplicitMessage($function, $message, $args = [])
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($message);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())->method('getHeaderLine')->with('Content-Type')
            ->willReturn('text/plain');
        $response->expects($this->once())->method('withStatus')->willReturnSelf();
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $controller->$function(...$args);
    }


    public function explicitMessageProvider()
    {
        return [
            ['badRequest'],
            ['paymentRequired'],
            ['forbidden'],
            ['notFound'],
            ['conflict'],
            ['tooManyRequests'],
            ['error']
        ];
    }

    /**
     * Test the default messages of different response methods
     * @dataProvider explicitMessageProvider
     *
     * @param string $function
     */
    public function testExplicitMessage($function)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with("Hello world");

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())->method('getHeaderLine')->with('Content-Type')
            ->willReturn('text/plain');
        $response->expects($this->once())->method('withStatus')->willReturnSelf();
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $controller->$function("Hello world");
    }


    /**
     * Test 'created' function with a location
     */
    public function testCreated()
    {
        $controller = $this->getController(['respondWith', 'setResponseHeader']);
        $controller->expects($this->once())->method('respondWith')->with(201);
        $controller->expects($this->once())->method('setResponseHeader')->with('Location', '/foo/bar');

        $controller->created('/foo/bar');
    }

    /**
     * Test 'partialContent' function with Range header
     */
    public function testPartialContent()
    {
        $controller = $this->getController(['respondWith', 'setResponseHeader']);
        $controller->expects($this->once())->method('respondWith')->with(206);
        $controller->expects($this->exactly(2))->method('setResponseHeader')->withConsecutive(
            ['Content-Range', 'bytes 100-200/500'],
            ['Content-Length', 100]
        );

        $controller->partialContent(100, 200, 500);
    }
    
    
    public function redirectStatusProvider()
    {
        return [
            [301],
            [302],
            ['307 Temporary Redirect']
        ];
    }
    
    /**
     * Test 'redirect' function
     * @dataProvider redirectStatusProvider
     * 
     * @param int|string $status
     */
    public function testRedirect($status)
    {
        $controller = $this->getController(['respondWith', 'setResponseHeader', 'output']);
        
        $controller->expects($this->once())->method('respondWith')->with($status);
        $controller->expects($this->once())->method('setResponseHeader')->with('Location', '/foo');
        $controller->expects($this->once())->method('output')
            ->with('You are being redirected to <a href="/foo">/foo</a>', 'text/html');
        
        $controller->redirect('/foo', $status);
    }

    /**
     * Provide data fot testing 'getLocalReferer' function
     *
     * @return array
     */
    public function localRefererProvider()
    {
        return [
            [null, '/'],
            ['/', '/'],
            ['/some/path', '/some/path']
        ];
    }

    /**
     * Test 'back' function
     * @dataProvider localRefererProvider
     * 
     * @param string $referer
     * @param string $location
     */
    public function testBack($referer, $location)
    {
        $controller = $this->getController(['getLocalReferer', 'respondWith', 'setResponseHeader', 'output']);
        
        $controller->expects($this->once())->method('getLocalReferer')->willReturn($referer);
        $controller->expects($this->once())->method('respondWith')->with(303);
        $controller->expects($this->once())->method('setResponseHeader')->with('Location', $location);
        $controller->expects($this->once())->method('output')
            ->with('You are being redirected to <a href="' . $location . '">' . $location . '</a>', 'text/html');
        
        $controller->back();
    }


    /**
     * Provide data for testing output
     *
     * @return array
     */
    public function outputProvider()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<document><tag>Test</tag></document>\n";
        
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        return [
            ['green beans', null, 'green beans', 'text/html'],
            ['hello world', 'text', 'hello world', 'text/plain'],
            ['abc();', 'js', 'abc();', 'application/javascript'],
            ['h1 { color: blue; }', 'css', 'h1 { color: blue; }', 'text/css'],
            ['{ "testKey": "testValue" }', 'json', '{ "testKey": "testValue" }', 'application/json'],
            [['testKey' => 'testValue'], 'json', '{"testKey":"testValue"}', 'application/json'],
            [simplexml_load_string($xml), 'xml', $xml, 'application/xml'],
            [$dom, 'xml', $xml, 'application/xml'],
            [$dom->firstChild->firstChild, 'xml', '<tag>Test</tag>', 'application/xml']
        ];
    }

    /**
     * Test output
     * @dataProvider outputProvider
     * 
     * @param mixed  $data
     * @param string $format
     * @param string $content      Expected content
     * @param string $contentType  Expected Content-Type
     */
    public function testOutputWithFormat($data, $format, $content, $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse', 'setResponseHeader']);
        $controller->method('getResponse')->willReturn($response);
        
        $controller->expects($this->once())->method('setResponseHeader')->with('Content-Type', $contentType);
        
        $controller->output($data, $format);
    }

    /**
     * Test output, getting the format from the Content-Type response header
     * @dataProvider outputProvider
     * 
     * @param mixed  $data
     * @param string $format
     * @param string $content      Expected content
     * @param string $contentType  Expected Content-Type
     */
    public function testOutputWithoutFormat($data, $format, $content, $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderline')->with('Content-Type')
            ->willReturn($format ? $contentType : '');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse', 'setResponseHeader']);
        $controller->method('getResponse')->willReturn($response);
        
        $controller->expects($format ? $this->never() : $this->once())->method('setResponseHeader')
            ->with('Content-Type', $contentType);
        
        $controller->output($data);
    }
    
    /**
     * Test output when using byDefaultSerializeTo
     * @dataProvider outputProvider
     * 
     * @param mixed  $data
     * @param string $format
     * @param string $content      Expected content
     * @param string $contentType  Expected Content-Type
     */
    public function testByDefaultSerializeTo($data, $format, $content, $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse', 'setResponseHeader']);
        $controller->method('getResponse')->willReturn($response);
        
        $controller->expects($this->once())->method('setResponseHeader')->with('Content-Type', $contentType);
        
        $controller->byDefaultSerializeTo($format);
        $controller->output($data);
    }
    
    /**
     * Test output when using byDefaultSerializeTo as fallback
     * @dataProvider outputProvider
     * 
     * @param mixed  $data
     * @param string $format
     * @param string $content      Expected content
     * @param string $contentType  Expected Content-Type
     */
    public function testByDefaultSerializeToFallback($data, $format, $content, $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($content);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderline')->with('Content-Type')->willReturn('text/plain');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse', 'setResponseHeader']);
        $controller->method('getResponse')->willReturn($response);
        
        $controller->expects(is_string($data) ? $this->never() : $this->once())->method('setResponseHeader')
            ->with('Content-Type', $contentType);
        
        $controller->byDefaultSerializeTo($format);
        $controller->output($data);
    }
    
    /**
     * Provide data for testing output
     *
     * @return array
     */
    public function unserializableProvider()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<document><tag>Test</tag></document>\n";
        
        $stringable = $this->createPartialMock('stdClass', ['__toString']);
        $stringable->method('__toString')->willReturn('I was an object');
        
        return [
            [['abc', 'def'], 'text/plain', "Unable to serialize array to 'text/plain'"],
            [['abc', 'def'], null, "Unable to serialize array to 'text/html'"],
            [new \stdClass(), 'text', "Unable to serialize stdClass object to 'text/plain'"],
            [new \stdClass(), 'xml', "Unable to serialize stdClass object to XML"],
        ];
    }

    /**
     * Test that serializeData throws an UnexpectedValueException
     * @dataProvider unserializableProvider
     * 
     * @param mixed  $data
     * @param string $format
     * @param string $message  Excpected exception message
     */
    public function testSerializeDataException($data, $format, $message)
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        $response = $this->createMock(ResponseInterface::class);

        $controller = $this->getController(['getResponse', 'setResponseHeader']);
        $controller->method('getResponse')->willReturn($response);
        
        $controller->output($data, $format);
    }
}
