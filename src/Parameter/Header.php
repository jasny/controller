<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Header implements Parameter
{
    use SingleParameter;

    /**
     * Get request header.
     */
    public function getValue(ServerRequestInterface $request, string $name, string $type, bool $required = false): mixed
    {
        $key = $this->key ?? str_replace('_', '-', $name);
        $value = $request->getHeaderLine($key);

        if ($required && $value === null) {
            throw new ParameterException("Missing required header '{$key}'");
        }

        return $this->filter($value, $type);
    }
}
