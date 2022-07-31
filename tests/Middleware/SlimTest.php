<?php

namespace Jasny\Test\Controller\Middleware;

use Cassandra\Exception\UnauthorizedException;
use Jasny\Controller\Controller;
use Jasny\Controller\Middleware\Slim;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpGoneException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Routing\Route;

/**
 * @covers \Jasny\Controller\Middleware\Slim
 */
class SlimTest extends TestCase
{
    public function testNop()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('__route__')
            ->willReturn(null);
        $request->expects($this->never())->method('withAttribute');

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $middleware = new Slim();
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testChangeCallable()
    {
        $controller = $this->createMock(Controller::class);

        $route = $this->createMock(Route::class);
        $route->expects($this->once())->method('getCallable')
            ->willReturn([$controller, 'foo']);
        $route->expects($this->once())->method('setCallable')
            ->with([$controller, '__invoke'])
            ->willReturnSelf();
        $route->method('getArguments')->willReturn([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('__route__')
            ->willReturn($route);
        $request->expects($this->exactly(2))->method('withAttribute')
            ->withConsecutive(['route:action', 'foo'], ['__route__', $route])
            ->willReturnSelf();

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $middleware = new Slim();
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function noChangeRouteProvider()
    {
        $controller = $this->createMock(Controller::class);

        return [
            ['foo'],
            [$controller],
            [['foo']],
            [[$controller, '__invoke']]
        ];
    }

    /**
     * @dataProvider noChangeRouteProvider
     */
    public function testDontChangeCallable($callable)
    {
        $route = $this->createMock(Route::class);
        $route->expects($this->once())->method('getCallable')
            ->willReturn($callable);
        $route->expects($this->never())->method('setCallable');
        $route->method('getArguments')->willReturn([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('__route__')
            ->willReturn($route);
        $request->expects($this->never())->method('withAttribute');

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $middleware = new Slim();
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testSetPathParameters()
    {
        $route = $this->createMock(Route::class);
        $route->expects($this->once())->method('getCallable')->willReturn('foo');

        $route->method('getArguments')->willReturn(['foo' => 42, 'bar' => 10]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('__route__')
            ->willReturn($route);
        $request->expects($this->exactly(2))->method('withAttribute')
            ->withConsecutive(['route:{foo}', 42], ['route:{bar}', 10])
            ->willReturnSelf();

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $middleware = new Slim(true);
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function httpErrorProvider()
    {
        return [
            'Bad Request' => [400, HttpBadRequestException::class],
            'Unauthorized' => [401, HttpUnauthorizedException::class],
            'Forbidden' => [403, HttpForbiddenException::class],
            'Not Found' => [404, HttpNotFoundException::class],
            'Method Not Allowed' => [405, HttpMethodNotAllowedException::class],
            'Gone' => [410, HttpGoneException::class],
            'Internal Server Error' => [500, HttpInternalServerErrorException::class],
            'Not Implemented' => [501, HttpNotImplementedException::class],
            'Payment Request' => [402, HttpException::class],
        ];
    }

    /**
     * @dataProvider httpErrorProvider
     */
    public function testSlimError(int $status, string $exception)
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('some error message');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')
            ->with('__route__')
            ->willReturn(null);
        $request->expects($this->never())->method('withAttribute');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn($status);
        $response->expects($this->once())->method('getBody')->willReturn($body);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);

        $middleware = new Slim(true);

        $this->expectException($exception);
        $this->expectExceptionMessage('some error message');

        $middleware->process($request, $handler);
    }
}
