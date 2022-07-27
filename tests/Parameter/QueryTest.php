<?php

namespace Jasny\Test\Controller\Parameter;

use Jasny\Controller\Parameter\Query;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class QueryTest extends TestCase
{
    protected Query $parameter;

    public function setUp(): void
    {
        $this->parameter = new Query();
    }

    public function test()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getQueryParams')
            ->willReturn(['number' => 42, 'color' => 'red']);

        $this->assertEquals(
            ['number' => 42, 'color' => 'red'],
            $this->parameter->getValue($request, 'foo', null)
        );
    }
}
