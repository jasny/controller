<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\PathParam;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\PathParam
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class PathParamTest extends TestCase
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
    public function test($consKey, $consType, $name, $type, $attribute, $expected)
    {
        $parameter = new PathParam($consKey, $consType);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('route:{' . $attribute . '}')
            ->willReturn('42');

        $value = $parameter->getValue($request, $name, $type);
        $this->assertEquals($expected, $value);
    }

    public function testMissingOptional()
    {
        $parameter = new PathParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('route:{foo}')
            ->willReturn(null);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new PathParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('route:{foo}')
            ->willReturn(null);

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required path parameter 'foo'");

        $parameter->getValue($request, 'foo', null, true);
    }

    public function testInvalidTypeInConstructor()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Undefined parameter type 'big'");

        new PathParam('foo', 'big');
    }
}
