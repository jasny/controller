<?php

namespace Jasny\Controller\Middleware;

use Jasny\Controller\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
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
 * Middleware to use controller in Slim framework
 */
class Slim implements MiddlewareInterface
{
    /**
     * @param bool $useSlimErrors  Throw Slim exceptions for error responses.
     */
    public function __construct(public bool $useSlimErrors = false)
    { }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute('__route__');

        if ($route instanceof Route) {
            $request = $this->changeCallable($request, $route);
            $request = $this->setPathParameters($request, $route);
        }

        $response = $handler->handle($request);

        if ($this->useSlimErrors) {
            $this->throwOnError($request, $response);
        }

        return $response;
    }

    protected function changeCallable(ServerRequestInterface $request, Route $route): ServerRequestInterface
    {
        $callable = $route->getCallable();

        if (is_array($callable) && is_a($callable[0], Controller::class, true) && isset($callable[1])) {
            $request = $request
                ->withAttribute("route:action", $callable[1])
                ->withAttribute('__route__', $route->setCallable([$callable[0], '__invoke']));
        }

        return $request;
    }

    protected function setPathParameters(ServerRequestInterface $request, Route $route): ServerRequestInterface
    {
        foreach ($route->getArguments() as $key => $value) {
            $request = $request->withAttribute("route:\{$key\}", $value);
        }

        return $request;
    }

    protected function throwOnError(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $status = $response->getStatusCode();

        switch ($status) {
            case 400: throw new HttpBadRequestException($request, (string)$response->getBody() ?: null);
            case 401: throw new HttpUnauthorizedException($request, (string)$response->getBody() ?: null);
            case 403: throw new HttpForbiddenException($request, (string)$response->getBody() ?: null);
            case 404: throw new HttpNotFoundException($request, (string)$response->getBody() ?: null);
            case 405: throw new HttpMethodNotAllowedException($request, (string)$response->getBody() ?: null);
            case 410: throw new HttpGoneException($request, (string)$response->getBody() ?: null);
            case 500: throw new HttpInternalServerErrorException($request, (string)$response->getBody() ?: null);
            case 501: throw new HttpNotImplementedException($request, (string)$response->getBody() ?: null);
        }

        if ($status >= 400) {
            throw new HttpException($request, (string)$response->getBody() ?: null, $status);
        }
    }
}