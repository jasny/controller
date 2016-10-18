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
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMockForAbstractClass();
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
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMockForAbstractClass();   
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
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMockForAbstractClass();
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
