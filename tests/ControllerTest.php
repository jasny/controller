<?php

use Jasny\Controller;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @param int $statusCode
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

        $this->assertEquals($data['isClientError'] || $data['isServerError'], $controller->isError(), "Method 'isError' returns incorrect value");
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
     * Test encodeData method, positive tests
     *
     * @dataProvider encodeDataPositiveProvider
     * @param mixed $data
     * @param string $contentType 
     * @param string $type 
     * @param string $callback  Callback name for testing jsonp request
     */
    public function testEncodeDataPositive($data, $contentType, $type, $callback = '')
    {
        $controller = $this->getController(['getRequest', 'getResponse']);
        list($request, $response) = $this->getRequests();

        $response->method('getHeaderLine')->with($this->equalTo('Content-Type'))->will($this->returnValue($contentType));
        if ($type === 'jsonp') {
            $request->method('getQueryParams')->will($this->returnValue(['callback' => $callback]));
        }

        $controller->method('getRequest')->will($this->returnValue($request));
        $controller->method('getResponse')->will($this->returnValue($response));

        $result = $controller->encodeData($data);
        $expect = null;

        if ($type === 'json') {
            $expect = json_encode($data);
        } elseif ($type === 'jsonp') {
            $expect = $callback . '(' . json_encode($data) . ')';
        } else {
            $expect = $data->asXML();
        }

        $this->assertNotEmpty($result);
        $this->assertEquals($expect, $result);
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
            [['testKey' => 'testValue'], 'application/json', 'json'],
            [['testKey' => 'testValue'], 'application/json', 'jsonp', 'test_callback'],
            ['', 'application/json', 'json'],
            ['', 'application/json', 'jsonp', 'test_callback'],
            [$xml, 'text/xml', 'xml'],            
            [$xml, 'application/xml', 'xml']
        ];
    }

    /**
     * Test encodeData method, negative tests
     *
     * @dataProvider encodeDataNegativeProvider
     * @param mixed $data
     * @param string $contentType 
     * @param string $type 
     * @param string $callback  Callback name for testing jsonp request
     */
    public function testEncodeDataNegative($data, $contentType, $type, $callback = '', $exception = true)
    {
        $controller = $this->getController(['getRequest', 'getResponse']);
        list($request, $response) = $this->getRequests();

        $response->method('getHeaderLine')->with($this->equalTo('Content-Type'))->will($this->returnValue($contentType));
        if ($type === 'jsonp') {
            $request->method('getQueryParams')->will($this->returnValue(['callback' => $callback]));
        }

        $controller->method('getRequest')->will($this->returnValue($request));
        $controller->method('getResponse')->will($this->returnValue($response));

        if ($exception) $this->expectException(\RuntimeException::class);

        $result = $controller->encodeData($data);
        $expect = null;

        if ($type === 'json') {
            $expect = json_encode($data);
        } elseif ($type === 'jsonp') {
            $expect = $callback . '(' . json_encode($data) . ')';
        } else {
            $expect = $data->asXML();
        }

        $this->assertNotEquals($expect, $result);
    }

    /**
     * Provide data for testing encodeData method
     *
     * @return array
     */
    public function encodeDataNegativeProvider()
    {
        $xml = simplexml_load_string(
            "<?xml version='1.0'?> 
             <document>
                 <tag1>Test tag</tag1>
                 <tag2>Test</tag2>
            </document>"
        );

        return [
            [['testKey' => 'testValue'], '', 'json'],
            [['testKey' => 'testValue'], 'json', 'json'],
            [['testKey' => 'testValue'], 'application/json', 'jsonp', '', false],
            [['testKey' => 'testValue'], 'application/jsonp', 'jsonp', 'test_callback'],
            [$xml, '', 'xml'],            
            [$xml, 'xml', 'xml']
        ];
    }

    /**
     * Test encoding data when response is not set
     */
    public function testEncodeDataNoResponse()
    {
        $controller = $this->getController();
        
        $this->expectException(\RuntimeException::class);

        $result = $controller->encodeData(['test' => 'test']);
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
}
