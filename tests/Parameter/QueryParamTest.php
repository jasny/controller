<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\QueryParam;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\QueryParam
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class QueryParamTest extends TestCase
{
    public function provider()
    {
        return [
            ['foo', 'int', 'bar', 'string', 'foo', 42],
            ['foo', null, 'bar', 'string', 'foo', '42'],
            [null, 'int', 'bar', 'string', 'bar', 42],
            [null, null, 'bar', 'string', 'bar', '42'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function test($consKey, $consType, $name, $type, $param, $expected)
    {
        $parameter = new QueryParam($consKey, $consType);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')
            ->willReturn([$param => '42', 'ot' => 10]);

        $value = $parameter->getValue($request, $name, $type);
        $this->assertEquals($expected, $value);
    }

    public function testMissingOptional()
    {
        $parameter = new QueryParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')
            ->willReturn(['ot' => 10]);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new QueryParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')
            ->willReturn(['ot' => 10]);

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required query parameter 'foo'");

        $parameter->getValue($request, 'foo', null, true);
    }

    public function testInvalidTypeInConstructor()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Undefined parameter type 'big'");

        new QueryParam('foo', 'big');
    }
}
