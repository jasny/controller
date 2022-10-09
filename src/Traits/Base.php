<?php

namespace Jasny\Controller\Traits;

use Jasny\Controller\Parameter\Parameter;
use Jasny\Controller\Parameter\PathParam;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base methods for controller and guard.
 */
trait Base
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    /**
     * Get request, set for controller
     */
    protected function getRequest(): ServerRequestInterface
    {
        if (!isset($this->request)) {
            throw new \LogicException("Request not set " . __CLASS__ . " has not been invoked"); // @codeCoverageIgnore
        }

        return $this->request;
    }

    /**
     * Get response, set for controller
     */
    protected function getResponse(): ResponseInterface
    {
        if (!isset($this->response)) {
            throw new \LogicException("Response not set " . __CLASS__ . " has not been invoked"); // @codeCoverageIgnore
        }

        return $this->response;
    }

    /**
     * Set the response
     */
    protected function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }


    /**
     * Get the arguments for a function from a route using reflection.
     */
    protected function getFunctionArgs(\ReflectionFunctionAbstract $refl): array
    {
        $args = [];

        foreach ($refl->getParameters() as $param) {
            [$argRefl] = $param->getAttributes(
                Parameter::class,
                \ReflectionAttribute::IS_INSTANCEOF
            ) + [null];
            $attribute = $argRefl?->newInstance() ?? new PathParam();

            $type = $param->getType();

            $args[] = $attribute->getValue(
                $this->request,
                $param->getName(),
                $type instanceof \ReflectionNamedType ? $type->getName() : null,
                !$param->isOptional(),
            ) ?? $param->getDefaultValue();
        }

        return $args;
    }
}
