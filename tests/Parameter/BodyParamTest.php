<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\BodyParam;
use Jasny\Controller\ParameterException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \Jasny\Controller\Parameter\BodyParam
 * @covers \Jasny\Controller\Parameter\SingleParameter
 */
class BodyParamTest extends TestCase
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
        $parameter = new BodyParam($consKey, $consType);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getParsedBody')
            ->willReturn([$param => '42', 'ot' => 10]);

        $value = $parameter->getValue($request, $name, $type);
        $this->assertEquals($expected, $value);
    }

    public function testMissingOptional()
    {
        $parameter = new BodyParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getParsedBody')
            ->willReturn(['ot' => 10]);

        $this->assertNull(
            $parameter->getValue($request, 'foo', null, false)
        );
    }

    public function testMissingRequired()
    {
        $parameter = new BodyParam();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getParsedBody')
            ->willReturn(['ot' => 10]);

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage("Missing required body parameter 'foo'");

        $parameter->getValue($request, 'foo', null, true);
    }

    public function testInvalidTypeInConstructor()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Undefined parameter type 'big'");

        new BodyParam('foo', 'big');
    }
}
