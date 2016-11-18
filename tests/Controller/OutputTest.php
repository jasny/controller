<?php

namespace Jasny\Controller;

use Jasny\Controller;
use Jasny\Flash;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Jasny\Controller\TestHelper;

/**
 * @covers Jasny\Controller\Output
 */
class OutputTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    /**
     * Provide data for testing error messages functions
     *
     * @return array
     */
    public function statusProvider()
    {
        return [
            ['ok', 200],
            ['created', 201],
            ['accepted', 202],
            ['noContent', 204],
            ['partialContent', 206],
            
            ['redirect', 303, ['example.com']],
            ['back', 303],
            ['notModified', 304],
            
            ['badRequest', 400],
            ['requireAuth', 401],
            ['requireLogin', 401],
            ['paymentRequired', 402],
            ['forbidden', 403],
            ['notFound', 404],
            ['conflict', 409],
            ['tooManyRequests', 429]
        ];
    }
    
    /**
     * Test functions that deal with error messages
     * @dataProvider statusProvider
     * 
     * @param string $function
     * @param int    $code      Status code
     * @param array  $args
     */
    public function testImplicitStatus($function, $code, $args = [])
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->any())->method('withHeader')->willReturnSelf();

        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $controller->$function(...$args);
    }

    
    /**
     * Test functions that deal with error messages
     * @dataProvider statusProvider
     * 
     * @param string $function
     * @param int    $code      Status code
     */
    public function testImplicitStatusMessage($function, $code, $args)
    {
        $message = 'Test message';

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($message);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        
        
        $this->assertEquals($result, $response, "Response object should be returned");
    }
    
    /**
     * Test setting flash
     *
     * @dataProvider flashProvider
     * @param object $data 
     */
    public function testFlash($data)
    {
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMockForAbstractClass();

        $flash = $controller->flash();
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEmpty($flash->get(), "Flash data should be empty");        

        $flash = $controller->flash($data->type, $data->message);
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEquals($data, $flash->get(), "Flash data is incorrect");

        $flash = $controller->flash();
        $this->assertInstanceOf(Flash::class, $flash, "Flash is not set");
        $this->assertEquals($data, $flash->get(), "Flash data is incorrect");

        $flash->clear();
    }

    /**
     * Test setting flash
     *
     * @return array
     */
    public function flashProvider()
    {
        return [
            [(object)['type' => 'test_type', 'message' => 'Test message']]
        ];  
    }

    /**
     * Test respondWith function
     *
     * @dataProvider respondWithProvider
     * @param int|string $code
     * @param string $format 
     * @param int $setCode      Actual code that will be set in response
     * @param string $contentType 
     */
    public function testRespondWith($code, $format, $setCode, $contentType)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                

        $this->expectResponseWith($response, $setCode, $contentType);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->respondWith($code, $format);

        $this->assertEquals($result, $response, "Response object should be returned");
    }

    /**
     * Test function respondWith
     *
     * @return array
     */
    public function respondWithProvider()
    {
        return [
            [200, 'json', 200, 'application/json'],
            [200, 'application/json', 200, 'application/json'],
            [204, null, 204, null],
            ['204 Created', null, 204, null],
            ['json', null, null, 'application/json']
        ];  
    }

    /**
     * Test functions that are simple wrappers around respondWith function
     *
     * @dataProvider respondWithWrappersProvider
     * @param string $function
     * @param int $code
     */
    public function testResponseWithWrappers($function, $code)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                

        $this->expectResponseWith($response, $code);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->{$function}();

        $this->assertEquals($result, $response, "Response object should be returned");   
    }

    /**
     * Provide data for testing respondWith wrappers
     *
     * @return array
     */
    public function respondWithWrappersProvider()
    {
        return [
            ['ok', 200],
            ['noContent', 204]
        ];
    }

    /**
     * Test 'created' function
     *
     * @dataProvider createdProvider
     * @param string $location
     */
    public function testCreated($location)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                

        $response->expects($this->once())->method('withStatus')->with($this->equalTo(201))->will($this->returnSelf());
        if ($location) {
            $response->expects($this->once())->method('withHeader')->with($this->equalTo('Location'), $this->equalTo($location))->will($this->returnSelf());
        }

        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->created($location);

        $this->assertEquals($result, $response, "Response object should be returned");   
    }

    /**
     * Provide data for testing 'created' function
     *
     * @return array
     */
    public function createdProvider()
    {
        return [
            [''], ['/some-path/test']
        ];
    }

    /**
     * Test 'redirect' function
     *
     * @dataProvider redirectProvider
     * @param string $url 
     * @param int $code 
     * @param boolean $default
     */
    public function testRedirect($url, $code, $default)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                        

        $this->expectRedirect($response, $url, $code);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $default ? 
            $controller->redirect($url) :
            $controller->redirect($url, $code);

        $this->assertEquals($result, $response, "Response object should be returned");   
    }

    /**
     * Provide data for testing 'redirect' function
     *
     * @return array
     */
    public function redirectProvider()
    {
        return [
            ['/test-url', 303, true], 
            ['/test-url', 301, false]
        ];
    }

    /**
     * Test 'requireLogin' function
     *
     * @dataProvider requireLoginProvider
     * @param string $function
     */
    public function testRequireLogin($function)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                        

        $this->expectRedirect($response, '/401', 303);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->{$function}();

        $this->assertEquals($result, $response, "Response object should be returned");   
    }

    /**
     * Provide data for testing 'requireLogon' function
     *
     * @return array
     */
    public function requireLoginProvider()
    {
        return [
            ['requireLogin'], ['requireAuth']
        ];
    }

    /**
     * Test 'getLocalReferer' funtion
     *
     * @dataProvider localRefererProvider
     * @param string $referer
     * @param string $host 
     * @param boolean $local
     */
    public function testLocalReferer($referer, $host, $local)
    {
        $controller = $this->getController(['getRequest']);
        list($request) = $this->getRequests();                        

        $this->expectLocalReferer($request, $referer, $host);
        $controller->method('getRequest')->will($this->returnValue($request));

        $result = $controller->getLocalReferer();

        $local ?
            $this->assertEquals($referer, $result, "Local referer should be returned") :      
            $this->assertEquals('', $result, "Local referer should not be returned");
    }

    /**
     * Test 'back' function
     *
     * @dataProvider localRefererProvider
     * @param string $referer
     * @param string $host 
     * @param boolean $local
     */
    public function testBack($referer, $host, $local)
    {
        $controller = $this->getController(['getRequest', 'getResponse']);
        list($request, $response) = $this->getRequests();        

        $this->expectLocalReferer($request, $referer, $host);
        $this->expectRedirect($response, $local ? $referer : '/', 303);

        $controller->method('getRequest')->will($this->returnValue($request));
        $controller->method('getResponse')->will($this->returnValue($response));        

        $result = $controller->back();

        $this->assertEquals($result, $response, "Response object should be returned");   
    }

    /**
     * Provide data fot testing 'getLocalReferer' function
     *
     * @return array
     */
    public function localRefererProvider()
    {
        return [
            ['http://not-local-host.com/path', 'local-host.com', false],
            ['http://local-host.com/path', 'local-host.com', true]
        ];  
    }

    /**
     * Expect for 'getLocalReferer' function to work correctly
     *
     * @param ServerRequestInterface $request
     * @param string $referer
     * @param string $host 
     */
    public function expectLocalReferer($request, $referer, $host)
    {
        $request->expects($this->exactly(2))->method('getHeaderLine')->withConsecutive(
            [$this->equalTo('HTTP_REFERER')],
            [$this->equalTo('HTTP_HOST')]
        )->will($this->returnCallback(function($header) use ($referer, $host) {
            return $header === 'HTTP_REFERER' ? $referer : $host;
        }));
    }

    /**
     * Expect for redirect
     *
     * @param ResponseInterface $response
     * @param string $url
     * @param int $code 
     */
    public function expectRedirect($response, $url, $code)     
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($this->equalTo('You are being redirected to <a href="' . $url . '">' . $url . '</a>'));   

        $response->expects($this->once())->method('getBody')->will($this->returnValue($stream));
        $response->expects($this->once())->method('withStatus')->with($this->equalTo($code))->will($this->returnSelf());
        $response->expects($this->exactly(2))->method('withHeader')->withConsecutive(
            [$this->equalTo('Content-Type'), $this->equalTo('text/html')],
            [$this->equalTo('Location'), $this->equalTo($url)]
        )->will($this->returnSelf());
    }

    /**
     * Expect correct work of respondWith function
     *
     * @param ResponseInterface $response
     * @param int $code
     * @param string $contentType
     */
    public function expectResponseWith($response, $code, $contentType = null)
    {
        $code ?
            $response->expects($this->once())->method('withStatus')->with($this->equalTo($code))->will($this->returnSelf()) :
            $response->expects($this->never())->method('withStatus')->with($this->equalTo($code));            

        $contentType ?
            $response->expects($this->once())->method('withHeader')->with($this->equalTo('Content-Type'), $this->equalTo($contentType))->will($this->returnSelf()) :
            $response->expects($this->never())->method('withHeader')->with($this->equalTo('Content-Type'), $this->equalTo($contentType));
    }

    /**
     * Expects that output will be set to content
     *
     * @param ResponseInterface $response
     * @param string $content 
     * @param string $contentType 
     */
    public function expectOutput($response, $content, $contentType)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($this->equalTo($content));

        $response->expects($this->once())->method('withHeader')->with($this->equalTo('Content-Type'), $this->equalTo($contentType))->will($this->returnSelf());
        $response->expects($this->once())->method('getBody')->will($this->returnValue($stream));
    }

    /**
     * Provide data for testing output
     *
     * @return array
     */
    public function outputProvider()
    {
        $xml = simplexml_load_string(
            "<?xml version='1.0'?> 
             <document>
                 <tag1>Test tag</tag1>
                 <tag2>Test</tag2>
            </document>"
        );

        return [
            ['test_string', 'text', 'text/plain'],
            ['javascript:test_call();', 'js', 'application/javascript'],
            ['test {}', 'css', 'text/css'],
            ['{ "testKey": "testValue" }', 'json', 'application/json'],
            [['testKey' => 'testValue'], 'json', 'application/json'],
            [$xml, 'xml', 'application/xml']
        ];
    }

    /**
     * Test output
     *
     * @dataProvider outputProvider
     * @param mixed $data
     * @param string $format
     * @param string $contentType
     */
    public function testOutput($data, $format, $contentType)
    {
        $response = $this->createMock(ResponseInterface::class);
        
        $controller = $this->getController(['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        if (is_scalar($data)) {
            $content = $data;
        } elseif ($format === 'json') {
            $content = json_encode($data);

            if ($callback) $content = "$callback($content)";                
        } elseif ($format === 'xml') {
            $content = $data->asXML();
        }

        $this->expectOutput($response, $content, $contentType);  

        if ($callback) {
            $request->method('getQueryParams')->will($this->returnValue(['callback' => $callback]));
        }      

        $controller->method('getRequest')->will($this->returnValue($request));
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->output($data, $format);

        $this->assertEquals($result, $response, "Output should return response instance");
    }
}
