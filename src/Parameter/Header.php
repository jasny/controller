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
        $key = $this->key ?? $this->convertNameToHeader($name);
        $value = $request->getHeaderLine($key);

        if ($required && $value === '') {
            throw new ParameterException("Missing required header '$key'");
        }

        return $this->filter($value, $type);
    }

    protected function convertNameToHeader(string $name): string
    {
        $sentence = preg_replace('/([a-z0-9])([A-Z])|(\w)_(\w)/', '$1$3-$2$4', $name);
        return str_replace(' ', '', ucwords($sentence, '-'));
    }
}
