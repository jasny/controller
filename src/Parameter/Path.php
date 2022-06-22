<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Path implements Parameter
{
    use SingleParameter;

    /**
     * Get request path parameter.
     */
    public function getValue(ServerRequestInterface $request, string $name, string $type, bool $required = false): mixed
    {
        $key = $this->key ?? $name;
        $value = $request->getAttribute("route:\{$key\}");

        if ($required && $value === null) {
            throw new ParameterException("Missing required path parameter '{$key}'");
        }

        return $this->filter($value, $type);
    }
}
