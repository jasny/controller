<?php

use Jasny\Controller;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers Jasny\Controller
 */
class ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test running controller
     */
    public function testInvoke()
    {
        $controller = $this->getController();
        list($request, $response) = $this->getRequests();

        $controller->expects($this->once())->method('run')->will($this->returnValue($response));

        $result = $controller($request, $response);

        $this->assertEquals($response, $result, "Invoking controller should return 'ResponseInterface' instance");
        $this->assertEquals($response, $controller->getResponse(), "Can not get 'ResponseInterface' instance from controller");
        $this->assertEquals($request, $controller->getRequest(), "Can not get 'ServerRequestInterface' instance from controller");
    }

    /**
     * Test response status functions if response object is not set
     */
    public function testResponseStatusEmptyResponse()
    {
        $controller = $this->getController();   
        $data = $this->getStatusCodesMap(null);

        foreach ($data as $func => $value) {
            $this->assertEquals($value, $controller->$func(), "Method '$func' returns incorrect value");
        }
    }

    /**
     * Test functions that check response status code
     *
     * @dataProvider responseStatusProvider
     * @param int  status code
     */
    public function testResponseStatus($code)
    {
        $controller = $this->getController();
        list($request, $response) = $this->getRequests();
        $response->method('getStatusCode')->will($this->returnValue($code));

        $controller($request, $response);                

        $data = $this->getStatusCodesMap($code);

        foreach ($data as $func => $value) {
            $this->assertEquals($value, $controller->$func(), "Method '$func' returns incorrect value");
        }

        $this->assertEquals($data['isClientError'] || $data['isServerError'], $controller->isError()
            , "Method 'isError' returns incorrect value");
    }

    /**
     * Provide data for testing status methods
     *
     * @return array
     */
    public function responseStatusProvider()
    {
        return [
            [null], [199],
            [200], [201], [299],
            [300], [304], [399],
            [400], [403], [499],
            [500], [503]
        ];
    }

    /**
     * Test functions that check request method
     *
     * @dataProvider requestMethodProvider
     * @param string $method
     */
    public function testRequestMethod($method)
    {
        $controller = $this->getController();
        list($request, $response) = $this->getRequests();
        $request->method('getMethod')->will($this->returnValue($method));

        $controller($request, $response);                

        $data = $this->getMethodsMap($method);

        foreach ($data as $func => $value) {
            $this->assertEquals($value, $controller->$func(), "Method '$func' returns incorrect value");
        }
    }

    /**
     * Provide data for testing functions that determine request method
     *
     * @return array
     */
    public function requestMethodProvider()
    {
        return [
            ['GET'], ['POST'], ['PUT'], ['DELETE'], ['HEAD']
        ];
    }
    
    /**
     * Test encodeData method, positive tests
     *
     * @dataProvider encodeDataPositiveProvider
     * @param mixed $data
     * @param string $format 
     * @param string $callback  Callback name for testing jsonp request
     */
    public function testEncodeDataPositive($data, $format, $callback = null)
    {
        $controller = $this->getController(['getRequest']);
        list($request) = $this->getRequests();

        if ($callback) {
            $request->method('getQueryParams')->will($this->returnValue(['callback' => $callback]));
        }

        $controller->method('getRequest')->will($this->returnValue($request));

        $result = $controller->encodeData($data, $format);
        $expect = null;

        if ($format === 'json') {
            $expect = json_encode($data);

            if ($callback) $expect = "$callback($expect)";                
        } else {
            $expect = $data->asXML();
        }

        $this->assertNotEmpty($result, "Result should not be empty");
        $this->assertEquals($expect, $result, "Data was not encoded correctly");
    }

    /**
     * Provide data for testing encodeData method
     *
     * @return array
     */
    public function encodeDataPositiveProvider()
    {
        $xml = simplexml_load_string(
            "<?xml version='1.0'?> 
             <document>
                 <tag1>Test tag</tag1>
                 <tag2>Test</tag2>
            </document>"
        );

        return [
            ['test_string', 'json'],
            [['testKey' => 'testValue'], 'json'],
            [['testKey' => 'testValue'], 'json', 'test_callback'],
            ['', 'json'],
            ['', 'json', 'test_callback'],
            [$xml, 'xml']
        ];
    }

    /**
     * Test encodeData method, negative tests
     *
     * @dataProvider encodeDataNegativeProvider
     * @param mixed $data
     * @param string $format
     */
    public function testEncodeDataNegative($data, $format)
    {
        $controller = $this->getController(['getRequest']);
        list($request) = $this->getRequests();

        $controller->method('getRequest')->will($this->returnValue($request));
        $this->expectException(\InvalidArgumentException::class);

        $result = $controller->encodeData($data, $format);
    }

    /**
     * Provide data for testing encodeData method
     *
     * @return array
     */
    public function encodeDataNegativeProvider()
    {
        return [
            ['test_string', 'html'],
            ['test_string', 'jpg']
        ];
    }

    /**
     * Test output
     *
     * @dataProvider outputProvider
     * @param mixed $data
     * @param string $format
     * @param string $contentType
     * @param string $callback     Callback name for testing jsonp request
     */
    public function testOutput($data, $format, $contentType, $callback = '')
    {
        $controller = $this->getController(['getRequest', 'getResponse']);
        list($request, $response) = $this->getRequests();        

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
            ['test_string', 'json', 'application/json'],
            [['testKey' => 'testValue'], 'json', 'application/json'],
            [['testKey' => 'testValue'], 'json', 'application/json', 'test_callback'],
            ['', 'json', 'application/json'],
            ['', 'json', 'application/json', 'test_callback'],
            [$xml, 'xml', 'application/xml']
        ];
    }

    /**
     * Test functions that deal with error messages
     *
     * @dataProvider errorMessagesProvider
     * @param string $function
     * @param int $code 
     * @param boolean $default   Is code default for this function
     */
    public function testErrorMessages($function, $code, $default)
    {
        $message = 'Test message';
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();        

        $this->expectErrorMessage($response, $message, $code);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $default ? 
            $controller->{$function}($message) :
            $controller->{$function}($message, $code);

        $this->assertEquals($result, $response, "Response object should be returned");
    }

    /**
     * Provide data for testing error messages functions
     *
     * @return array
     */
    public function errorMessagesProvider()
    {
        return [
            ['error', 400, true],
            ['error', 403, false],
            ['tooManyRequests', 429, true],
            ['tooManyRequests', 400, false],
            ['conflict', 409, true],
            ['conflict', 403, false],
            ['notFound', 404, true],
            ['notFound', 400, false],
            ['forbidden', 403, true],
            ['forbidden', 409, false],
            ['badRequest', 400, true],
            ['badRequest', 403, false]
        ];  
    }

    /**
     * Test responseWith function
     *
     * @dataProvider responseWithProvider
     * @param int|string $code
     * @param string $format 
     * @param int $setCode      Actual code that will be set in response
     * @param string $contentType 
     */
    public function testResponseWith($code, $format, $setCode, $contentType)
    {
        $controller = $this->getController(['getResponse']);
        list(, $response) = $this->getRequests();                

        $this->expectResponseWith($response, $setCode, $contentType);
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->responseWith($code, $format);

        $this->assertEquals($result, $response, "Response object should be returned");
    }

    /**
     * Test function responseWith
     *
     * @return array
     */
    public function responseWithProvider()
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
     * Test functions that are simple wrappers around responseWith function
     *
     * @dataProvider responseWithWrappersProvider
     * @param string $functino
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
     * Provide data for testing responseWith wrappers
     *
     * @return array
     */
    public function responseWithWrappersProvider()
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
     * Expect correct work of responseWith function
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
     * Expect for correct work of error message functions
     *
     * @param ResponseInterface $response
     * @param string $message 
     * @param int $code
     */
    public function expectErrorMessage($response, $message, $code)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with($this->equalTo($message));

        $response->expects($this->once())->method('withStatus')->with($this->equalTo($code))->will($this->returnSelf());
        $response->expects($this->once())->method('getBody')->will($this->returnValue($stream));
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
     * Get mock for controller
     *
     * @param array $methods  Methods to mock
     * @return Controller
     */
    public function getController($methods = [])
    {
        $builder = $this->getMockBuilder(Controller::class)->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }

        return $builder->getMockForAbstractClass();
    }

    /**
     * Get request and response instances
     *
     * @return array
     */
    public function getRequests()
    {
        return [
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(ResponseInterface::class)            
        ];
    }
    
    /**
     * Get map of status codes to states
     *
     * @param int $code
     * @return []
     */
    public function getStatusCodesMap($code)
    {
        return [
            'isSuccessful' => !$code || ($code >= 200 && $code < 300),
            'isRedirection' => $code >= 300 && $code < 400,
            'isClientError' => $code >= 400 && $code < 500,
            'isServerError' => $code >= 500            
        ];
    }

    /**
     * Get map of request methods
     *
     * @param string $method
     * @return array
     */
    public function getMethodsMap($method)
    {
        return [
            'isGetRequest' => $method === 'GET',
            'isPostRequest' => $method === 'POST',
            'isPutRequest' => $method === 'PUT',
            'isDeleteRequest' => $method === 'DELETE',
            'isHeadRequest' => $method === 'HEAD'
        ];
    }
}
