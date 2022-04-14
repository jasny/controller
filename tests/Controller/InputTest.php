<?php

namespace Jasny\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Jasny\Traits\TestHelper;

/**
 * @covers Jasny\Controller\Input
 */
class InputTest extends \PHPUnit_Framework_TestCase
{
    use TestHelper;

    public function queryParamsProvider()
    {
        return [
            [null, null, 'wall',[]],
            [[], null, 'wall', []],
            [['foo' => 10], 10, 'wall', []],
            [
                ['foo' => 10, 'bar' => '', 'zoo' => ['monkey', 'lion'], 'qux' => ['a' => 10, 'b' => 20]],
                10, '', ['a' => 10, 'b' => 20]
            ]
        ];
    }
    
    /**
     * @dataProvider queryParamsProvider
     * 
     * @param array|null $data
     */
    public function testGetQueryParams($data)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')->willReturn($data);
        
        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->willReturn($request);
        
        $params = $controller->getQueryParams();
        $this->assertSame((array)$data, $params);
    }
    
    /**
     * @dataProvider queryParamsProvider
     * 
     * @param array|null $data
     * @param int        $foo
     * @param string     $bar
     * @param array      $qux
     */
    public function testListQueryParams($data, $foo, $bar, $qux)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')->willReturn($data);
        
        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->willReturn($request);
        
        $list = $controller->getQueryParams(['bar' => 'wall', 'foo', 'qux' => []]);
        $this->assertEquals([$bar, $foo, $qux], $list);
    }
    
    
    
    public function testHasQueryParams()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->any())->method('getQueryParams')
            ->willReturn(['foo' => 10, 'bar' => '', 'buz' => null, 'qux' => ['a' => 10, 'b' => 20]]);
        
        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->willReturn($request);
        
        $this->assertTrue($controller->hasQueryParam('foo'), 'foo');
        $this->assertTrue($controller->hasQueryParam('bar'), 'bar');
        $this->assertTrue($controller->hasQueryParam('qux'), 'qux');
        
        $this->assertFalse($controller->hasQueryParam('nop'), 'nop');
        $this->assertFalse($controller->hasQueryParam('buz'), 'buz');
    }
    
    public function getQueryParamProvider()
    {
        return [
            [10, 'foo'],
            ['', 'bar'],
            [null, 'buz'],
            [['a' => 10], 'qux'],
            [null, 'nop'],
            
            [10, 'foo', 22],
            ['', 'bar', 'woop'],
            ['woop', 'buz', 'woop'],
            ['woop', 'nop', 'woop'],
            
            [10, 'foo', null, FILTER_SANITIZE_NUMBER_INT],
            [10, 'fox', null, FILTER_SANITIZE_NUMBER_INT],
            [22, 'nop', '22 pigs', FILTER_SANITIZE_NUMBER_INT],
            [null, 'nop', 'woop', FILTER_SANITIZE_NUMBER_INT],
            
            [10, 'foo', null, FILTER_VALIDATE_INT],
            [false, 'fox', null, FILTER_VALIDATE_INT],
            [false, 'nop', '22 pigs', FILTER_VALIDATE_INT],
            [null, 'nop', null, FILTER_VALIDATE_INT],
            
            [10, 'foo', null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE],
            [null, 'fox', null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE],
            [null, 'nop', '22 pigs', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE],
            [null, 'nop', null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE]
        ];
    }
    
    /**
     * @dataProvider getQueryParamProvider
     * 
     * @param mixed  $expect
     * @param array  $param
     * @param string $default
     * @param int    $filter
     * @param array  $filterOptions
     */
    public function testGetQueryParam($expect, ...$args)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->any())->method('getQueryParams')
            ->willReturn(['foo' => 10, 'fox' => '10 foxes', 'bar' => '', 'buz' => null, 'qux' => ['a' => 10]]);
        
        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->willReturn($request);
        
        $value = $controller->getQueryParam(...(array)$args);
        
        $this->assertEquals($expect, $value);
    }
    
    
    public function inputProvider()
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $qux = $this->createMock(UploadedFileInterface::class);
        
        return [
            [
                [],
                [],
                [],
            ],
            [
                ['foo' => 10],
                [],
                ['foo' => 10],
            ],
            [
                ['foo' => 10],
                null,
                ['foo' => 10],
            ],
            [
                ['foo' => 10, 'bar' => '', 'buz' => null, 'qux' => ['a' => 10, 'b' => 20]],
                [],
                ['foo' => 10, 'bar' => '', 'buz' => null, 'qux' => ['a' => 10, 'b' => 20]],
            ],
            [
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20]],
                ['file' => $file],
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20], 'file' => $file],
            ],
            [
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20]],
                ['foo' => $file],
                ['foo' => $file, 'qux' => ['a' => 10, 'b' => 20]],
            ],
            [
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20]],
                ['file' => $file, 'qux' => ['c' => $qux]],
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20, 'c' => $qux], 'file' => $file],
            ],
            [
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => 20]],
                ['file' => $file, 'qux' => ['b' => $qux]],
                ['foo' => 10, 'qux' => ['a' => 10, 'b' => $qux], 'file' => $file],
            ],
            [
                [],
                ['foo' => $file],
                ['foo' => $file]
            ],
            [
                null,
                ['foo' => $file],
                null
            ],
            [
                new \stdClass(),
                ['foo' => $file],
                new \stdClass(),
            ]
        ];
    }
    
    /**
     * @dataProvider inputProvider
     * 
     * @param array|mixed $data
     * @param array|null  $files
     * @param array|mixed $expect
     */
    public function testGetInput($data, $files, $expect)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->any())->method('getParsedBody')->willReturn($data);
        $request->expects($this->any())->method('getUploadedFiles')->willReturn($files);
        
        $controller = $this->getController(['getRequest']);
        $controller->method('getRequest')->willReturn($request);
        
        $input = $controller->getInput();
        
        $this->assertEquals($expect, $input);
    }
}
