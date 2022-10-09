<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Attr;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\Attr
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class AttributeTest extends TestCase
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
    public function test($consKey, $consType, $name, $type, $Attribute, $expected)
    {
        $parameter = new Attr($consKey, $consType);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with($Attribute)
            ->willReturn('42');

        $value = $parameter->getValue($request, $name, $type);
        $this->assertEquals($expected, $value);
    }

    public function testMissingOptional()
    {
        $parameter = new Attr();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('foo')
            ->willReturn(null);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new Attr();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('foo')
            ->willReturn(null);

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required request attribute 'foo'");

        $parameter->getValue($request, 'foo', null, true);
    }

    public function testInvalidTypeInConstructor()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Undefined parameter type 'big'");

        new Attr('foo', 'big');
    }
}
