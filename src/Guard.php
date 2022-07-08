<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
abstract class Guard
{
    use Traits\Base,
        Traits\Header,
        Traits\Output,
        Traits\CheckRequest,
        Traits\CheckResponse;

    /**
     * @return void|null|ResponseInterface|$this
     */
    abstract public function process();

    /**
     * Invoke guard.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface|null
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ?ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;

        $args = $this->getFunctionArgs(new \ReflectionMethod($this, 'process'));

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $result = $this->process(...$args);

        return $result === $this ? $this->getResponse() : $result;
    }
}
