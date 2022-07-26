<?php

namespace Jasny\Test\Controller\Traits;

use Jasny\Controller\Controller;
use Jasny\Test\Controller\InContextOf;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Jasny\Controller\Traits\Header
 */
class HeaderTest extends TestCase
{
    use InContextOf;

    public function statusProvider()
    {
        return [
            '200' => [200, 200, ''],
            '"200"' => ['200', 200, ''],
            '"200 Yes"' => ['200 Yes', 200, 'Yes'],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStatus(int|string $status, int $code, string $phrase)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code, $phrase)->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse', 'output']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn () => $controller->status($status));
    }

    public function testHeader()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withHeader')
            ->with('foo', 'bar')
            ->willReturnSelf();
        $response->expects($this->never())->method('withAddedHeader');

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn () => $controller->header('foo', 'bar'));
    }

    public function testAddedHeader()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->never())->method('withHeader');
        $response->expects($this->once())->method('withAddedHeader')
            ->with('foo', 'bar')
            ->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn () => $controller->header('foo', 'bar', true));
    }

    public function implicitStatusCodeProvider()
    {
        return [
            'ok' => ['ok', 200],
            'created' => ['created', 201],
            'accepted' => ['accepted', 202],
            'noContent' => ['noContent', 204],
            'partialContent' => ['partialContent', 206, 1, 2, 100],

            'redirect' => ['redirect', 303, 'example.com'],
            'notModified' => ['notModified', 304],

            'badRequest' => ['badRequest', 400],
            'unauthorized' => ['unauthorized', 401],
            'paymentRequired' => ['paymentRequired', 402],
            'forbidden' => ['forbidden', 403],
            'notFound' => ['notFound', 404],
            'conflict' => ['conflict', 409, ['']],
            'tooManyRequests' => ['tooManyRequests', 429],

            'error' => ['error', 500]
        ];
    }

    /**
     * Test the default status code of different response methods
     * @dataProvider implicitStatusCodeProvider
     */
    public function testImplicitStatus(string $function, int $code, ...$args)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->any())->method('withHeader')->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse', 'output']);
        $controller->method('getResponse')->willReturn($response);

        $this->inContextOf($controller, fn () => $controller->$function(...$args));
    }


    public function explicitStatusCodeProvider()
    {
        return [
            'noContent' => ['noContent', 205],
            '301 redirect' => ['redirect', 301, 'example.com'],
            '307 redirect' => ['redirect', 307, 'example.com'],
            'badRequest' => ['badRequest', 412],
            'notFound' => ['notFound', 405],
            'error' => ['error', 501]
        ];
    }

    /**
     * Test setting the status code of different response methods
     * @dataProvider explicitStatusCodeProvider
     */
    public function testExlicitStatus(string $function, int $code, ...$args)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with($code)->willReturnSelf();
        $response->expects($this->any())->method('withHeader')->willReturnSelf();

        $controller = $this->createPartialMock(Controller::class, ['getResponse', 'output']);
        $controller->method('getResponse')->willReturn($response);

        $args[] = $code;

        $this->inContextOf($controller, fn () => $controller->$function(...$args));
    }

    public function invalidStatusProvider()
    {
        return [
            'noContent' => ["noContent", "Invalid status code 100 for no content response", 100],
            'redirect' => ["redirect", "Invalid status code 100 for redirect", '/', 100],
            'badRequest' => ["badRequest", "Invalid status code 100 for bad request response", 100],
            'notFound' => ["notFound", "Invalid status code 100 for no content response", 100],
            'error' => ["error", "Invalid status code 100 for server error response", 100]
        ];
    }

    /**
     * @dataProvider invalidStatusProvider
     */
    public function testInvalidStatus(string $function, string $message, ...$args)
    {
        $controller = $this->createPartialMock(Controller::class, ['getResponse']);
        $controller->expects($this->never())->method('getResponse');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $this->inContextOf($controller, fn () => $controller->$function(...$args));
    }

    /**
     * Test 'created' function with a location
     */
    public function testCreated()
    {
        $controller = $this->createPartialMock(Controller::class, ['status', 'header']);
        $controller->expects($this->once())->method('status')->with(201);
        $controller->expects($this->once())->method('header')->with('Location', '/foo/bar');

        $this->inContextOf($controller, fn () => $controller->created('/foo/bar'));
    }

    /**
     * Test 'partialContent' function with Range header
     */
    public function testPartialContent()
    {
        $controller = $this->createPartialMock(Controller::class, ['status', 'header']);
        $controller->expects($this->once())->method('status')->with(206)->willReturnSelf();
        $controller->expects($this->exactly(2))->method('header')
            ->withConsecutive(
                ['Content-Range', 'bytes 100-200/500'],
                ['Content-Length', 100]
            )
            ->willReturnSelf();

        $this->inContextOf($controller, fn () => $controller->partialContent(100, 200, 500));
    }
    
    
    public function redirectStatusProvider()
    {
        return [
            301 => [301],
            302 => [302],
            '307 Temporary Redirect' => ['307 Temporary Redirect']
        ];
    }
    
    /**
     * Test 'redirect' function
     * @dataProvider redirectStatusProvider
     */
    public function testRedirect(int|string $status)
    {
        $controller = $this->createPartialMock(Controller::class, ['status', 'header', 'output']);
        
        $controller->expects($this->once())->method('status')->with($status)->willReturnSelf();
        $controller->expects($this->once())->method('header')
            ->with('Location', '/foo')
            ->willReturnSelf();
        $controller->expects($this->once())->method('output')
            ->with('You are being redirected to <a href="/foo">/foo</a>', 'text/html')
            ->willReturnSelf();

        $this->inContextOf($controller, fn () => $controller->redirect('/foo', $status));
    }

    /**
     * Provide data fot testing 'getLocalReferer' function
     *
     * @return array
     */
    public function localRefererProvider()
    {
        return [
            [null, '/'],
            ['/', '/'],
            ['/some/path', '/some/path']
        ];
    }

    /**
     * Test 'back' function
     * @dataProvider localRefererProvider
     */
    public function testBack(?string $referer, string $location)
    {
        $controller = $this->createPartialMock(Controller::class, ['getLocalReferer', 'status', 'header', 'output']);
        
        $controller->expects($this->once())->method('getLocalReferer')->willReturn($referer);
        $controller->expects($this->once())->method('status')->with(303)->willReturnSelf();
        $controller->expects($this->once())->method('header')
            ->with('Location', $location)
            ->willReturnSelf();
        $controller->expects($this->once())->method('output')
            ->with('You are being redirected to <a href="' . $location . '">' . $location . '</a>', 'text/html')
            ->willReturnSelf();

        $this->inContextOf($controller, fn () => $controller->back());
    }
}
