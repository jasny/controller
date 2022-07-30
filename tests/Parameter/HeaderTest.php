<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Header;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\Header
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class HeaderTest extends TestCase
{
    public function provider()
    {
        return [
            ['Foo', 'int', 'bar', 'string', 'Foo', 42],
            ['Foo', null, 'bar', 'string', 'Foo', '42'],
            [null, 'int', 'bar', 'string', 'Bar', 42],
            [null, null, 'bar', 'string', 'Bar', '42'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function test($consKey, $consType, $name, $type, $header, $expected)
    {
        $parameter = new Header($consKey, $consType);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')
            ->with($header)
            ->willReturn('42');

        $value = $parameter->getValue($request, $name, $type);
        $this->assertEquals($expected, $value);
    }

    public function testMissingOptional()
    {
        $parameter = new Header();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')
            ->with('Foo')
            ->willReturn(null);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new Header();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getHeaderLine')
            ->with('Foo')
            ->willReturn('');

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required header 'Foo'");

        $parameter->getValue($request, 'foo', null, true);
    }

    public function testInvalidTypeInConstructor()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Undefined parameter type 'big'");

        new Header('foo', 'big');
    }
}
